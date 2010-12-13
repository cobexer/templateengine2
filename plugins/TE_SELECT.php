<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2010, Obexer Christoph
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
	foreach($val as $values) {
		$html .= '	<option value="'.$values['VALUE'].'">'.$values['NAME'].'</option>'; //FIXME: call escape methods for both variables
	}
	return $html;
}

TemplateEngine :: registerPlugin('TE_SELECT', '/\{SELECT=([A-Z0-9_]+)\}/', 'TE_PLUGIN_TE_SELECT');

//EOF
