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

function TE_PLUGIN_ESC_LEN($value, $config) {
	if(is_array($value)) {
		return count($value);
	}
	if(is_string($value)) {
		return strlen($value);
	}
	return 0; //everything else is unknown atm
}

TemplateEngine :: registerEscapeMethod('LEN', 'TE_PLUGIN_ESC_LEN');

//EOF