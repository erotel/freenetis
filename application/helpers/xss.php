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
 * Escapes a value for safe HTML output.
 *
 * SafeHtml instances pass through unchanged (the HTML is already trusted).
 * All other values are converted to string and run through htmlspecialchars().
 *
 * This is the canonical short-hand to use in view templates instead of the
 * longer html::specialchars() call:
 *
 *   <?php echo e($member->name) ?>
 *   <?php echo e($user->login) ?>
 *   <?php echo e($contact->value) ?>
 *
 * @param  mixed $value
 * @return string  HTML-safe string
 */
function e($value)
{
	if ($value instanceof SafeHtml)
	{
		return (string) $value;
	}
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
