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

function TE_PLUGIN_TE_LOAD_WITHID(array $ctx, array $match) {
	$content = '';
	TemplateEngine :: LogMsg('[LOAD_WITHID]', true, TEMode :: debug, false);
	$succ = TemplateEngine :: getFile($match[1], $content);
	return $succ ? str_replace("{LOAD:ID}", $match[2], $content) : false;
}

TemplateEngine :: registerPlugin('TE_LOAD_WITHID', '/\{LOAD_WITHID=([^\{\}]+);([^\{\}]+)\}/', 'TE_PLUGIN_TE_LOAD_WITHID');

//EOF