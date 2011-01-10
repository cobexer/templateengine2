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
require_once(dirname(__FILE__) . '/../TemplateEngineTestBase.php');

class TE_FOREACH_FILE_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_FOREACH_FILE.php');/* /RM */
		/* RM */require_once('plugins/TE_SCALAR.php');/* /RM */
	}



	public function testNonArrayForeach() {
		TemplateEngine::set('VARIABLE', false);
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/non-array.tpl', false));
		$this->assertEquals('{FOREACH[VARIABLE]=plugins/TE_FOREACH_FILE/dummy.tpl}', $result, 'foreach does not process non arrays');
	}

	public function testEmptyArray() {
		TemplateEngine::set('ELEMENT', array());
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/simple-elements.tpl', false));
		$this->assertEquals('No elements available!', $result, 'foreach loads /file/-empty.tpl if array is empty');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/simple-elements.tpl', false));
		$this->assertEquals('No elements available!', $result, 'foreach loads /file/-empty.tpl if array is empty(and did not modify the context)');
		TemplateEngine::set('VARIABLE', array());
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/empty-file-missing.tpl', false));
		$this->assertEquals('{FOREACH[VARIABLE]=plugins/TE_FOREACH_FILE/dummy.tpl}', $result, 'foreach does not process empty arrays if -empty.tpl is missing');
	}

	public function testRejectsNonArrayElements() {
		TemplateEngine::set('ELEMENT', array(array(), 'invalid', array()));
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/simple-elements.tpl', false));
		$this->assertEquals('{FOREACH[ELEMENT]=plugins/TE_FOREACH_FILE/simple-element.tpl}', $result, 'foreach file rejects arrays with non array elements');
	}

	public function testSimpleArray() {
		TemplateEngine::set('ELEMENT', array(array('NAME'=>'Phone', 'AMOUNT'=>5), array('NAME'=>'Netbook', 'AMOUNT'=>2)));
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/simple-elements.tpl', false));
		$this->assertEquals("0: Phone #5 (odd)\n1: Netbook #2 ()", $result, 'simple array correctly processed');
	}

	public function testComplexNestedForeachFile() {
		TemplateEngine::set('COMPLEX', array(
			'Phone'=>array(
				'PROPERTY'=>array(
					'Manufacturer'=>array(
						'VALUE'=>'ABC'
					),
					'Price'=>array(
						'VALUE'=>999
					)
				)
			),
			'Netbook'=>array(
				'PROPERTY'=>array(
					'Manufacturer'=>array(
						'VALUE'=>'DEF'
					),
					'Price'=>array(
						'VALUE'=>666
					)
				)
			)));
		TemplateEngine::set('COMPLEX_SCOPELOOKUP', array(array('VALUE'=>'success')));
		$result = str_replace("\n", "", trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/complex-elements.tpl', false)));
		$expected = '<ul><li class="odd">Phone:<br/><dl><dt>Manufacturer</dt><dd>ABC</dd><dt>Price</dt><dd>999</dd></dl><br/>success</li>';
		$expected .= '<li class="">Netbook:<br/><dl><dt>Manufacturer</dt><dd>DEF</dd><dt>Price</dt><dd>666</dd></dl><br/>success</li></ul>';
		$this->assertEquals($expected, $result, 'complex array correctly processed');
	}

	public function testScopeChainLookupOrder() {
		TemplateEngine::set('VARIABLE', 'global');
		TemplateEngine::set('ARRAY', array(array('VARIABLE'=>'scope')));
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/scope-chain-lookup.tpl', false));
		$this->assertEquals("global:scope", $result, 'scope chain lookup');
	}

	public function testScopeChainLookup() {
		TemplateEngine::set('VARIABLE', 'global');
		TemplateEngine::set('ARRAY', array(array()));
		$result = trim(TemplateEngine::processTemplate('plugins/TE_FOREACH_FILE/scope-chain-lookup.tpl', false));
		$this->assertEquals("global:global", $result, 'scope chain lookup');
	}
}
