<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2010-2012, Obexer Christoph
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: @DATE@
 * @author Obexer Christoph
 * @version @VERSION@ (@COMMIT@)
 * @package TemplateEngine2
 */

function TE_PLUGIN_TE_IF(array $ctx, array $match) {
	$key = $match['variable'];
	$escaper = $match['escaper'];
	$op = $match['operator'];
	$literal = null;
	if(isset($match['literal']) && '' !== $match['literal']) {
		$literal = $match['literal'];
	}
	elseif(!TemplateEngine :: lookupVar($match['litvar'], $literal)) {
		TemplateEngine :: LogMsg('[IF]: Value <em>'.$match['litvar'].'</em> not set, but used by IF', false, TEMode::error);
		return false;
	}
	$block = $match['block'];
	$nblock = isset($match['nblock']) ? $match['nblock'] : '';
	$val = isset($ctx[$key]) ? $ctx[$key] : null;
	if(null == $val && 'null' !== $literal && !TemplateEngine :: lookupVar($key, $val)) {
		TemplateEngine :: LogMsg('[IF]: Value <em>'.$key.'</em> not set, but used by IF', false, TEMode::error);
		return false;
	}
	$result= false;
	//< maybe not best style but easy and works =)
	if('null' == $literal) {
		$literal = null;
	}
	TemplateEngine :: LogMsg('[IF]: Condition: <em>'.$key.('' !== $escaper ? '|'.$escaper : '').' '.$op.' '.($literal === null ? 'null' : $literal).'</em> ', true, TEMode::debug, false);
	if ('' !== $escaper) {
		$val = TemplateEngine :: escape($escaper, $val);
	}
	switch($op) {
		case '<' :
		case 'lt' :
			$result = $val < $literal;
			break;
		case '>' :
		case 'gt' :
			$result = $val > $literal;
			break;
		case '==' :
		case 'eq' :
			$result = $val == $literal;
			break;
		case '!=' :
		case 'ne' :
			$result = $val != $literal;
			break;
		case '<=' :
		case 'lte' :
			$result = $val <= $literal;
			break;
		case '>=' :
		case 'gte' :
			$result = $val >= $literal;
			break;
	}
	TemplateEngine :: LogMsg('... ' . ($result === true ? '' : 'not ') . 'matched!', true, TEMode::debug);
	return ($result === true ? $block : $nblock);
}

TemplateEngine :: registerPlugin('TE_IF',
	'/\{(IF)\((?P<variable>[A-Z0-9_]+)(?:\|(?P<escaper>[A-Z_]+))?\s?(?P<operator><|>|==|!=|<=|>=|lt|gt|eq|ne|lte|gte){1}\s?(?:(?P<literal>[\w-]+)|\{(?P<litvar>[A-Z0-9_]+)\})\)\}(?P<block>(?:(?'.'>[^{]*?)|(?:\{)(?!(IF\(([A-Z0-9_]+)(?:\|([A-Z_]+))?\s?(<|>|==|!=|<=|>=|lt|gt|eq|ne|lte|gte){1}\s?([\w-]+)\)\}))|(?R))*)(\{IF:ELSE\}(?P<nblock>(?:(?'.'>[^{]*?)|(?:\{)(?!(IF\(([A-Z0-9_]+)(?:\|([A-Z_]+))?\s?(<|>|==|!=|<=|>=|lt|gt|eq|ne|lte|gte){1}\s?([\w-]+)\)\}))|(?R))*))?\{\/IF\}/Us',
	'TE_PLUGIN_TE_IF');

//EOF
