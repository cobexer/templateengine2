<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2010-2014, Obexer Christoph
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: @DATE@
 * @author Obexer Christoph
 * @version @VERSION@ (@COMMIT@)
 * @package TemplateEngine2
 */

TemplateEngine::registerEscapeMethod('LEN', function($value, $config) {
	if(is_array($value)) {
		return count($value);
	}
	if(is_string($value)) {
		return strlen($value);
	}
	return 0; //everything else is unknown atm
});
