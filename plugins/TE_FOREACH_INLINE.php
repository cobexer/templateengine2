<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2010-2015, Obexer Christoph
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: @DATE@
 * @author Obexer Christoph
 * @version @VERSION@ (@COMMIT@)
 * @package TemplateEngine2
 */

function TE_PLUGIN_TE_FOREACH_INLINE(array $ctx, array $match) {
	$val = null;
	$found = false;
	if (isset($ctx[$match['variable']])) {
		$val = $ctx[$match['variable']];
		$found = true;
	}
	elseif (TemplateEngine::lookupVar($match['variable'], $val)) {
		$found = true;
	}

	if(!$found || !is_array($val)) {
		TemplateEngine::LogMsg('[FOREACH_INLINE]: Variable <em>'.$match['variable'].'</em> not set or invalid', false, TEMode::error);
		return false;
	}
	$block = $match['block'];
	if(empty($val)) {
		$block = isset($match['nblock']) ? $match['nblock'] : '';
		$val[] = array();
	}
	$res = '';
	$iteration = 0;
	foreach($val as $index => $lctx) {
		if (!is_array($lctx)) {
			TemplateEngine::LogMsg('[FOREACH_INLINE]: Variable <em>'.$match[1].'</em> contained invalid element', false, TEMode::error);
			return false;
		}
		$lctx['ODDROW'] = (($iteration % 2) == 0) ? 'odd' : '';
		$res .= str_replace('{FOREACH:INDEX}', $index, TemplateEngine::pushContext($block, $lctx));
		$iteration++;
	}
	return $res;
}

TemplateEngine::registerPlugin('TE_FOREACH_INLINE', '/\{FOREACH\[(?P<variable>' . TE_regex_varname . ')\]\}(?P<block>(?:(?'.'>[^{]*?)|(?:\{)(?!(FOREACH\[(' . TE_regex_varname . ')\]\}))|(?R))*)(?:\{FOREACH:ELSE\}(?P<nblock>(?:(?'.'>[^{]*?)|(?:\{)(?!(FOREACH\[(' . TE_regex_varname . ')\]\}))|(?R))*))?\{\/FOREACH\}/Us', 'TE_PLUGIN_TE_FOREACH_INLINE');

//EOF
