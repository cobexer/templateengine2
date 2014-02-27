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
	switch($match[1]) {
		case 'DEBUG':  TemplateEngine :: setMode(TEMode :: debug);  return '';
		case 'WARNING':TemplateEngine :: setMode(TEMode :: warning);return '';
		case 'ERROR':  TemplateEngine :: setMode(TEMode :: error);  return '';
		case 'NONE':   TemplateEngine :: setMode(TEMode :: none);   return '';
		default: return false;
	}
}

TemplateEngine :: registerPlugin('TE_LOGLEVEL', '/\{LOGLEVEL=(DEBUG|WARNING|ERROR|NONE)\}/', 'TE_PLUGIN_TE_LOGLEVEL');

//EOF