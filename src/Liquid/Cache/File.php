<?php

namespace Liquid\Cache;

use Liquid\Cache;
use Liquid\LiquidException;

/**
 * Implements cache stored in files.
 */
class File extends Cache
{
	/**
	 * Constructor.
	 *
	 * It checks the availability of cache directory.
	 *
	 * @throws LiquidException if Cachedir not exists.
	 */
	public function __construct($options = array()) {
		parent::__construct($options);

		if (isset($options['cache_dir']) && is_writable($options['cache_dir']))
			$this->_path = realpath($options['cache_dir']) . DIRECTORY_SEPARATOR;
		else
			throw new LiquidException('Cachedir not exists or not writable');
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($key, $unserialize = true) {
		if (!$this->exists($key))
			return false;

		if ($unserialize)
			return unserialize(file_get_contents($this->_path . $this->_prefix . $key));

		return file_get_contents($this->_path . $this->_prefix . $key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists($key) {
		$cacheFile = $this->_path . $this->_prefix . $key;

		if (!file_exists($cacheFile) || @filemtime($cacheFile) + $this->_expire < time())
			return false;

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function write($key, $value, $serialize = true) {
		if (@file_put_contents($this->_path . $this->_prefix . $key, $serialize ? serialize($value) : $value) !== false) {
			$this->gc();
			return true;
		}

		throw new LiquidException('Can not write cache file');
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush($expiredOnly = false) {
		foreach (glob($this->_path . $this->_prefix . '*') as $file) {
			if ($expiredOnly) {
				if (@filemtime($file) + $this->_expire < time())
					@unlink($file);
			} else
				@unlink($file);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function gc() {
		$this->flush(true);
	}
}