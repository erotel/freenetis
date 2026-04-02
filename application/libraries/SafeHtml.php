<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 *
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 *
 * More info about project can be found:
 * http://www.freenetis.org/
 *
 */

/**
 * Value object that marks a string as already-escaped, trusted HTML.
 *
 * Use SafeHtml::make($html) to wrap HTML that was produced by framework
 * helpers (html::anchor, Grid::render, Forge::html, …) when passing it
 * to view variables or html::anchor() as the link-text argument.
 *
 * Without this wrapper, html::anchor() and View::__set() will apply
 * htmlspecialchars() to the value, which would entity-encode the markup.
 *
 * Usage:
 *   // In a controller – wrap HTML before assigning to a view variable:
 *   $view->content->links = SafeHtml::make(implode(' | ', $anchor_array));
 *
 *   // In html::anchor() – link text that contains an icon image:
 *   html::anchor('devices/add', SafeHtml::make(html::image('ico.png')));
 *
 * @package Libraries
 */
class SafeHtml
{
	/** @var string */
	private $html;

	private function __construct($html)
	{
		$this->html = (string) $html;
	}

	/**
	 * Wraps a trusted HTML string so that it passes through escaping helpers
	 * without being double-encoded.
	 *
	 * @param  string|SafeHtml $html
	 * @return SafeHtml
	 */
	public static function make($html)
	{
		if ($html instanceof self)
		{
			return $html;
		}
		return new self($html);
	}

	/**
	 * Returns the raw HTML string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->html;
	}
}
