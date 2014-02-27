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

function TE_PLUGIN_TE_STRIP_INLINESTYLE(array $context, array $match) {
	return '';
}

// strip all inline styles if 'no_inline' is set in $_GET
if(isset($_GET['no_inline'])) {
	TemplateEngine :: registerPlugin('TE_STRIP_INLINESTYLE', '/(style="(?:[^"]*)")/', 'TE_PLUGIN_TE_STRIP_INLINESTYLE');
}

//EOF