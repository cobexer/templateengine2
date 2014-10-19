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

function TE_PLUGIN_TE_LOGLEVEL(array $ctx, array $match) {
	$result = false;
	switch($match[1]) {
		case 'DEBUG':  TemplateEngine::setMode(TEMode::debug);  $result = ''; break;
		case 'WARNING':TemplateEngine::setMode(TEMode::warning);$result = ''; break;
		case 'ERROR':  TemplateEngine::setMode(TEMode::error);  $result = ''; break;
		case 'NONE':   TemplateEngine::setMode(TEMode::none);   $result = ''; break;
	}
	return $result;
}

TemplateEngine::registerPlugin('TE_LOGLEVEL', '/\{LOGLEVEL=(DEBUG|WARNING|ERROR|NONE)\}/', 'TE_PLUGIN_TE_LOGLEVEL');

//EOF
