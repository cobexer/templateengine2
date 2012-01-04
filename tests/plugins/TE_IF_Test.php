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
require_once(dirname(__FILE__) . '/../TemplateEngineTestBase.php');

class TE_IF_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_IF.php');/* /RM */
		/* RM */require_once('plugins/TE_SCALAR.php');/* /RM */
	}

	public function testVarBoolean() {
		TemplateEngine::set('VARBOOL', true);
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('plugins/TE_IF/bool.tpl', false)));
		TemplateEngine::set('VARBOOL', false);
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('plugins/TE_IF/bool.tpl', false)));
	}

	public function testVarText() {
		TemplateEngine::set('VARTEXT', "some_text");
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('plugins/TE_IF/string.tpl', false)));
		TemplateEngine::set('VARTEXT', "some_other_text");
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('plugins/TE_IF/string.tpl', false)));
	}

	public function testVarNumber() {
		TemplateEngine::set('VARNUMBER', 42);
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('plugins/TE_IF/number.tpl', false)));
		TemplateEngine::set('VARNUMBER', 1337);
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('plugins/TE_IF/number.tpl', false)));
	}

	public function testNumericOperators() {
		TemplateEngine::set('VAR', 42);
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/numeric-operators.tpl', false));
		$expected = 'gte:>=:lte:<=:eq:==:eq:==:gte:>=:lte:<=';
		$this->assertEquals($expected, $result, "VAR(42) op 42: operators evaluate the correct values");
		TemplateEngine::set('VAR', 1337);
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/numeric-operators.tpl', false));
		$expected = 'gte:>=:gt:>:ne:!=:ne:!=:gte:>=:gt:>';
		$this->assertEquals($expected, $result, "VAR(1337) op 42: operators evaluate the correct values");
		TemplateEngine::set('VAR', 2);
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/numeric-operators.tpl', false));
		$expected = 'lt:<:lte:<=:ne:!=:ne:!=:lt:<:lte:<=';
		$this->assertEquals($expected, $result, "VAR(2) op 42: operators evaluate the correct values");
	}

	public function testNullLiteral() {
		TemplateEngine::set('VAR', null);
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/null-literal.tpl', false));
		$expected = 'eq:==:eq:==';
		$this->assertEquals($expected, $result, "VAR(null) ne|!=|eq|== null: operators evaluate the correct values");
		TemplateEngine::set('VAR', true);
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/null-literal.tpl', false));
		$expected = 'ne:!=:ne:!=';
		$this->assertEquals($expected, $result, "VAR(true) ne|!=|eq|== null: operators evaluate the correct values");
		TemplateEngine::delete('VAR');
		$this->assertEquals($this, TemplateEngine::get('VAR', $this), 'VAR deleted and not set anymore');
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/null-literal.tpl', false));
		$expected = 'eq:==:eq:==';
		$this->assertEquals($expected, $result, "VAR(undefined) ne|!=|eq|== null: operators evaluate the correct values");
	}

	public function testVarVar() {
		TemplateEngine::set('VAR1', '42');
		TemplateEngine::set('VAR2', '42');
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/var-op-var.tpl', false));
		$expected = 'gte:>=:lte:<=:eq:==:eq:==:gte:>=:lte:<=';
		$this->assertEquals($expected, $result, "VAR1(42) op {VAR2}(42): operators evaluate the correct values");
		TemplateEngine::set('VAR2', 1337);
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/var-op-var.tpl', false));
		$expected = 'lt:<:lte:<=:ne:!=:ne:!=:lt:<:lte:<=';
		$this->assertEquals($expected, $result, "VAR1(42) op {VAR2}(1337): operators evaluate the correct values");
		TemplateEngine::set('VAR2', 2);
		$result = str_replace(array("\n", "\r"), "", TemplateEngine::processTemplate('plugins/TE_IF/var-op-var.tpl', false));
		$expected = 'gte:>=:gt:>:ne:!=:ne:!=:gte:>=:gt:>';
		$this->assertEquals($expected, $result, "VAR1(42) op {VAR2}(2): operators evaluate the correct values");
	}

	public function testVarUndefined() {
		TemplateEngine::delete('VARNUMBER');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_IF/number.tpl', false));
		$expected = '{IF(VARNUMBER eq 42)}true{IF:ELSE}false{/IF}';
		$this->assertEquals($expected, $result, 'if the variable is not set, the if is rejected');
	}

	public function testVarVarUndefined() {
		TemplateEngine::delete('VAR1');
		TemplateEngine::delete('VAR2');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_IF/varvarundefined.tpl', false));
		$expected = '{IF(VAR1 eq {VAR2})}true{IF:ELSE}false{/IF}';
		$this->assertEquals($expected, $result, '(none set) if the variable is not set, the if is rejected');
		TemplateEngine::set('VAR1', '42');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_IF/varvarundefined.tpl', false));
		$this->assertEquals($expected, $result, '(VAR2 not set) if the variable is not set, the if is rejected');
		TemplateEngine::delete('VAR1');
		TemplateEngine::set('VAR2', '42');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_IF/varvarundefined.tpl', false));
		$this->assertEquals(str_replace('{VAR2}', 42, $expected), $result, '(VAR1 not set) if the variable is not set, the if is rejected');
	}

	private $TE_IF_ESC_called = false;
	public function TE_IF_ESC($variable, $config) {
		$this->TE_IF_ESC_called = true;
		return 1337;
	}
	public function testEscapeMethodSupport() {
		TemplateEngine::set('VAR1', 42);
		$this->TE_IF_ESC_called = false;
		TemplateEngine::registerEscapeMethod('TE_IF_ESC', array($this, 'TE_IF_ESC'));
		$result = trim(TemplateEngine::processTemplate('plugins/TE_IF/escape.tpl', false));
		$this->assertEquals(true, $this->TE_IF_ESC_called, 'escape method called');
		$this->assertEquals('true', $result, 'variable escaped and escaped variable used');
	}
}

//EOF
