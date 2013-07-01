<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2010-2013, Obexer Christoph
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
	if ($found && isset($match[2]) && '' != $match[2]) {
		return TemplateEngine :: escape($match[2], $val);
	}
	elseif ($found) {
		return (string)$val;
	}
	return false;
}

TemplateEngine :: registerPlugin('TE_SCALAR', '/\{(' . TE_regex_varname . ')(?:\|(' . TE_regex_escape_method . '))?\}/', 'TE_PLUGIN_TE_SCALAR');

//EOF
