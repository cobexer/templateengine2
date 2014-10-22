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

TemplateEngine::registerPlugin('TE_ASSET', '/\{ASSET:([^\{\}]+)\}/', function(array $ctx, array $match) {
	$path = TemplateEngine::lookupFile($match[1]);
	return $path ? $path : false;
});
