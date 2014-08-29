<?php

namespace Liquid;

use Liquid\Tag\Document;

/**
 * The Template class.
 *
 * Example:
 *
 *     $tpl = new \Liquid\Template();
 *     $tpl->parse(template_source);
 *     $tpl->render(array('foo'=>1, 'bar'=>2);
 */
class Template
{
	/**
	 * @var Document The _root of the node tree
	 */
	private $root;

	/**
	 * todo
	 * @var BlankFileSystem The file system to use for includes
	 */
	private $fileSystem;

	/**
	 * @var array Globally included filters
	 */
	private $filters = array();

	/**
	 * @var array Custom tags
	 */
	private static $tags = array();

	/**
	 * @var Cache
	 */
	private static $cache;

	/**
	 * Constructor.
	 *
	 * @param string $path
	 * @param array|Cache $cache
	 *
	 * @return Template
	 */
	public function __construct($path = null, $cache = null) {
		$this->fileSystem = $path !== null
			? new LocalFileSystem($path)
			: new BlankFileSystem();

		$this->setCache($cache);
	}

	/**
	 * // todo
	 * @param string $fileSystem
	 */
	public function setFileSystem($fileSystem) {
		$this->fileSystem = $fileSystem;
	}

	/**
	 * @param array|Cache $cache
	 *
	 * @throws LiquidException
	 */
	public function setCache($cache) {
		if (is_array($cache)) {
			if (isset($cache['cache']) && class_exists('\Liquid\Cache' . ucwords($cache['cache']))) {
				$classname = '\Liquid\Cache' . ucwords($cache['cache']);
				self::$cache = new $classname($cache);
			} else {
				throw new LiquidException('Invalid cache options!');
			}
		} else if ($cache instanceof Cache) {
			self::$cache = $cache;
		}
	}

	/**
	 * @return Cache
	 */
	public static function getCache() {
		return self::$cache;
	}

	/**
	 * @return Document
	 */
	public function getRoot() {
		return $this->root;
	}

	/**
	 * Register custom Tags
	 *
	 * @param string $name
	 * @param string $class
	 */
	public function registerTag($name, $class) {
		self::$tags[$name] = $class;
	}

	/**
	 * @return array
	 */
	public static function getTags() {
		return self::$tags;
	}

	/**
	 * Register the filter
	 *
	 * @param string $filter
	 */
	public function registerFilter($filter) {
		$this->filters[] = $filter;
	}

	/**
	 * Tokenizes the given source string
	 *
	 * @param string $source
	 *
	 * @return array
	 */
	public static function tokenize($source) {
		return empty($source)
			? array()
			: preg_split(Liquid::LIQUID_TOKENIZATION_REGEXP, $source, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	}

	/**
	 * Parses the given source string
	 *
	 * @param string $source
	 *
	 * @return Template
	 */
	public function parse($source) {
		if (self::$cache !== null) {
			if (($this->root = self::$cache->read(md5($source))) != false && $this->root->checkIncludes() != true) {
			} else {
				$this->root = new Document(Template::tokenize($source), $this->fileSystem);
				self::$cache->write(md5($source), $this->root);
			}
		} else {
			$this->root = new Document(Template::tokenize($source), $this->fileSystem);
		}

		return $this;
	}

	/**
	 * Renders the current template
	 *
	 * @param array $assigns an array of values for the template
	 * @param array $filters additional filters for the template
	 * @param array $registers additional registers for the template
	 *
	 * @return string
	 */
	public function render(array $assigns = array(), $filters = null, $registers = null) {
		$context = new Context($assigns, $registers);

		if (!is_null($filters)) {
			if (is_array($filters)) {
				array_merge($this->filters, $filters);
			} else {
				$this->filters[] = $filters;
			}
		}

		foreach ($this->filters as $filter) {
			$context->addFilters($filter);
		}

		return $this->root->render($context);
	}
}
