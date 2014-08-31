<?php

namespace Liquid\Tag;

use Liquid\Liquid;
use Liquid\Context;
use Liquid\LiquidException;
use Liquid\BlankFileSystem;
use Liquid\Regexp;

/**
 * Used to increment a counter into a template
 *
 * Example:
 *
 *     {% increment value %}
 *
 * @author Viorel Dram
 */
class TagIncrement extends AbstractTag
{
	/**
	 * Name of the variable to increment
	 *
	 * @var string
	 */
	private $toIncrement;

	/**
	 * Constructor
	 *
	 * @param string $markup
	 * @param array $tokens
	 * @param BlankFileSystem $fileSystem
	 *
	 * @throws \Liquid\LiquidException
	 */
	public function __construct($markup, array &$tokens, $fileSystem) {
		$syntax = new Regexp("/(" . Liquid::LIQUID_ALLOWED_VARIABLE_CHARS . "+)/");

		if ($syntax->match($markup)) {
			$this->toIncrement = $syntax->matches[0];
		} else {
			throw new LiquidException("Syntax Error in 'increment' - Valid syntax: increment [var]");
		}
	}

	/**
	 * Renders the tag
	 *
	 * @param Context $context
	 *
	 * @return string|void
	 */
	public function render(Context $context) {
		// If the value is not set in the environment check to see if it
		// exists in the context, and if not set it to -1
		if (!isset($context->environments[0][$this->toIncrement])) {
			// check for a context value
			$from_context = $context->get($this->toIncrement);

			// we already have a value in the context
			$context->environments[0][$this->toIncrement] = (null !== $from_context) ? $from_context : -1;
		}

		// Increment the value
		$context->environments[0][$this->toIncrement]++;

		return '';
	}
}
