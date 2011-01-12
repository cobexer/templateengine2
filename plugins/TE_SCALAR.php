<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2011, Obexer Christoph
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: @DATE@
 * @author Obexer Christoph
 * @version @VERSION@ (@COMMIT@)
 * @package TemplateEngine2
 */

function TE_PLUGIN_TE_SCALAR(array $ctx, array $match) {
	$val = null;
	$found = false;
	if (isset($ctx[$match[1]])) {
		$val = $ctx[$match[1]];
		$found = true;
	}
	elseif (TemplateEngine :: lookupVar($match[1], $val)) {
		$found = true;
	}
	if ($found && isset($match['escaper']) && '' != $match['escaper']) {
		return TemplateEngine :: escape($match['escaper'], $val);
	}
	elseif ($found) {
		return (string)$val;
	}
	return false;
}

TemplateEngine :: registerPlugin('TE_SCALAR', '/\{([A-Z0-9_]*)(?:\|(?P<escaper>[A-Z0-9_]+))?\}/', 'TE_PLUGIN_TE_SCALAR');

//EOF
