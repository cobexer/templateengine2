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

class TE_SCALAR_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_SCALAR.php');/* /RM */
	}

	public function testVarBoolean() {
		TemplateEngine::set('VARIABLE', true);
		$this->assertEquals("1", trim(TemplateEngine::processTemplate('plugins/TE_SCALAR/scalar.tpl', false)), 'boolean converted to string results in a number');
	}

	public function testVarNumber() {
		TemplateEngine::set('VARIABLE', 1337);
		$this->assertEquals("1337", trim(TemplateEngine::processTemplate('plugins/TE_SCALAR/scalar.tpl', false)), 'number used as is');
	}

	public function testVarText() {
		TemplateEngine::set('VARIABLE', "Text as is.");
		$this->assertEquals("Text as is.", trim(TemplateEngine::processTemplate('plugins/TE_SCALAR/scalar.tpl', false)), 'string used as is');
	}

	public function testVarHTML() {
		TemplateEngine::set('VARIABLE', "<strong>HTML</strong> as is.");
		$this->assertEquals("<strong>HTML</strong> as is.", trim(TemplateEngine::processTemplate('plugins/TE_SCALAR/scalar.tpl', false)), 'HTML used as is');
	}

	public function testVarTemplateCode() {
		TemplateEngine::set('VARIABLE', "Template code: {ROOT_PATH}{UNDEFINED_VAR}");
		$this->assertEquals("Template code: " . TemplateEngine::getRootPath() . "{UNDEFINED_VAR}", trim(TemplateEngine::processTemplate('plugins/TE_SCALAR/scalar.tpl', false)), 'Template code used and executed as expected');
	}

	public function testEscaperSupport() {
		TemplateEngine::registerEscapeMethod('TE_TEST_SCALAR_ESCAPER_SUPPORT', array($this, 'TE_TEST_SCALAR_ESCAPER_SUPPORT'));
		TemplateEngine::set('VARIABLE', 'original');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_SCALAR/escaper.tpl', false));
		$this->assertEquals(true, $this->TE_TEST_SCALAR_ESCAPER_SUPPORT_executed, 'TE_SCALAR supports escape methods');
		$this->assertEquals('escaped', $result, 'TE_SCALAR supports escape methods');
	}

	private $TE_TEST_SCALAR_ESCAPER_SUPPORT_executed = false;
	public function TE_TEST_SCALAR_ESCAPER_SUPPORT($value, $config) {
		$this->TE_TEST_SCALAR_ESCAPER_SUPPORT_executed = true;
		return "escaped";
	}

	private $TE_TEST_SCALAR_LOOKUP_SCOPE_CHAIN_SUPPORTED_executed = false;
	public function TE_TEST_SCALAR_LOOKUP_SCOPE_CHAIN_SUPPORTED(array $context, array $match) {
		$this->TE_TEST_SCALAR_LOOKUP_SCOPE_CHAIN_SUPPORTED_executed = true;
		$content = false;
		if (TemplateEngine::getFile($match[1], $content)) {
			$content = TemplateEngine::pushContext($content, array());
		}
		return $content;
	}

	public function testSkalarSupportsLookupScopeChain() {
		TemplateEngine::registerPlugin('TE_TEST_SCALAR_LOOKUP_SCOPE_CHAIN_SUPPORTED', '/\{TE_TEST_SCALAR_LOOKUP_SCOPE_CHAIN_SUPPORTED=([^\{\}]+)\}/', array($this, 'TE_TEST_SCALAR_LOOKUP_SCOPE_CHAIN_SUPPORTED'));
		TemplateEngine::set('VARIABLE', 'success');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_SCALAR/lookupScopeChain.tpl', false));
		$this->assertEquals(true, $this->TE_TEST_SCALAR_LOOKUP_SCOPE_CHAIN_SUPPORTED_executed, 'TE_SCALAR supports scope chain lookups');
		$this->assertEquals('success', $result, 'TE_SCALAR supports scope chain lookups');
	}
}

//EOF
