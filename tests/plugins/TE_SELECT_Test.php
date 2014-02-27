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
require_once(dirname(__FILE__) . '/../TemplateEngineTestBase.php');

class TE_SELECT_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_SELECT.php');/* /RM */
	}

	public function testSelect() {
		TemplateEngine::set('OPTIONS', array(array('NAME'=>'ABC', 'VALUE'=>'#0001'),array('NAME'=>'DEF', 'VALUE'=>'#0002')));
		$result = str_replace(array("\n", "\t"), "", trim(TemplateEngine::processTemplate('plugins/TE_SELECT/select.tpl', false)));
		$expected = '<option value="#0001">ABC</option><option value="#0002">DEF</option>';
		$this->assertEquals($expected, $result, 'option tags rendered as expected');
	}

	public function TE_SELECT_CHAIN(array $mathc, array $context) {
		//execute the actual interesting directive
		return trim(TemplateEngine::pushContext('{SELECT=OPTIONS}', array()));
	}

	public function testSelectChainLookup() {
		$opt = array(array('NAME'=>'ABC', 'VALUE'=>'#0001'),array('NAME'=>'DEF', 'VALUE'=>'#0002'));
		TemplateEngine::registerPlugin('TE_SELECT_CHAIN', '/\{TE_SELECT_CHAIN!\}/', array($this, 'TE_SELECT_CHAIN'));
		$result = str_replace(array("\n", "\t"), "", trim(TemplateEngine::pushContext('{TE_SELECT_CHAIN!}', array('OPTIONS'=>$opt))));
		$expected = '<option value="#0001">ABC</option><option value="#0002">DEF</option>';
		$this->assertEquals($expected, $result, 'option tags rendered as expected');
	}


	public function testSelectRejectsNonArrayVariables() {
		TemplateEngine::set('OPTIONS', 'INVALID');
		$result = str_replace(array("\n", "\t"), "", trim(TemplateEngine::processTemplate('plugins/TE_SELECT/select.tpl', false)));
		$expected = '{SELECT=OPTIONS}';
		$this->assertEquals($expected, $result, 'select rejects non array variables');
	}

	public function testSelectLowercaseVariables() {
		TemplateEngine::set('options', array(array('name'=>'ABC', 'value'=>'#0001'),array('name'=>'DEF', 'value'=>'#0002')));
		$result = str_replace(array("\n", "\t"), "", trim(TemplateEngine::processTemplate('plugins/TE_SELECT/select-lowercase.tpl', false)));
		$expected = '<option value="#0001">ABC</option><option value="#0002">DEF</option>';
		$this->assertEquals($expected, $result, 'option tags rendered as expected');
	}
}
