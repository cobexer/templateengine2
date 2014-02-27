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

function TE_PLUGIN_TE_SELECT(array $ctx, array $match) {
	$html = '';
	$val = null;
	if(isset($ctx[$match[1]])) {
		$val = $ctx[$match[1]];
	}
	else {
		TemplateEngine :: lookupVar($match[1], $val);
	}
	if(!is_array($val)) {
		TemplateEngine :: LogMsg('[TE_SELECT]: Array <em>"'.$match[1].'"</em> not set or invalid', false, TEMode :: error);
		return false;
	}
	TemplateEngine :: LogMsg('[TE_SELECT]: rendering Array <em>"'.$match[1].'"</em>', true, TEMode :: debug);
	foreach($val as $index => $values) {
		$value = '';
		$name = '';
		if (isset($values['VALUE'])) {
			$value = $values['VALUE'];
		}
		elseif(isset($values['value'])) {
			$value = $values['value'];
		}
		else {
			TemplateEngine :: LogMsg('[TE_SELECT]: invalid array item at index ' . $index, false, TEMode :: error);
		}
		if (isset($values['NAME'])) {
			$name = $values['NAME'];
		}
		elseif(isset($values['name'])) {
			$name = $values['name'];
		}
		else {
			TemplateEngine :: LogMsg('[TE_SELECT]: invalid array item at index ' . $index, false, TEMode :: error);
		}
		$html .= '	<option value="'.$value.'">'.$name.'</option>'; //FIXME: call escape methods for both variables
	}
	return $html;
}

TemplateEngine :: registerPlugin('TE_SELECT', '/\{SELECT=(' . TE_regex_varname . ')\}/', 'TE_PLUGIN_TE_SELECT');

//EOF
