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

function TE_PLUGIN_TE_FOREACH_FILE(array $ctx, array $match) {
	$val = null;
	$found = false;
	if (isset($ctx[$match[1]])) {
		$val = $ctx[$match[1]];
		$found = true;
	}
	elseif (TemplateEngine :: lookupVar($match[1], $val)) {
		$found = true;
	}

	if(!$found || !is_array($val)) {
		TemplateEngine :: LogMsg('[FOREACH_FILE]: Variable <em>'.$match[1].'</em> not set or invalid', false, TEMode::error);
		return false;
	}
	$fname = $match[2];
	if(empty($val)) {
		$fname = str_replace('.tpl', '-empty.tpl', $fname);
		$val[] = array(); //append empty element to make the rest work
	}
	$tpl = '';
	TemplateEngine :: LogMsg('[FOREACH_FILE]', true, TEMode :: debug, false);
	$succ = TemplateEngine :: getFile($fname, $tpl);
	if (!$succ) {
		return false;
	}
	$res = '';
	$iteration = 0;
	foreach($val as $index => $lctx) {
		if (!is_array($lctx)) {
			TemplateEngine :: LogMsg('[FOREACH_FILE]: Variable <em>'.$match[1].'</em> contained invalid element', false, TEMode::error);
			return false;
		}
		$lctx['ODDROW'] = (($iteration % 2) == 0) ? 'odd' : '';
		$res .= str_replace('{FOREACH:INDEX}', $index, TemplateEngine :: pushContext($tpl, $lctx));
		$iteration++;
	}
	return $res;
}

TemplateEngine :: registerPlugin('TE_FOREACH_FILE', '/\{FOREACH\[(' . TE_regex_varname . ')\]=([^\}]+)\}/Um', 'TE_PLUGIN_TE_FOREACH_FILE');

//EOF
