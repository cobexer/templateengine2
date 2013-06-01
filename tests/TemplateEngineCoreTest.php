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
require_once(dirname(__FILE__) . '/TemplateEngineTestBase.php');

class TemplateEngineCoreTest extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_SCALAR.php');/* /RM */
	}

	public function testSetRootPath() {
		TemplateEngine::setRootPath(dirname(__FILE__));
		$this->assertEquals(dirname(__FILE__) . '/', TemplateEngine::getRootPath(), 'set root path gets / added at the end');
		TemplateEngine::setTemplatePath('templates');
		$this->assertEquals(dirname(__FILE__) . '/', trim(TemplateEngine::processTemplate('te-core-rootpath.tpl', false)), 'root path available to templates');
	}

	public function testSetTemplatePath() {
		TemplateEngine::setTemplatePath('coverage-templates');
		$this->assertEquals('coverage-templates/', TemplateEngine::getTemplatePath(), 'set template path gets / added at the end');
		TemplateEngine::setTemplatePath('templates');
		$this->assertEquals(TemplateEngine::getRootPath() . TemplateEngine::getTemplatePath(), trim(TemplateEngine::processTemplate('te-core-templatepath.tpl', false)), 'template path available to templates');
	}

	public function testSetAndGet() {
		$this->assertEquals(null, TemplateEngine::get('non-existing-template-variable'), 'default for unknown variables is null');
		$this->assertEquals(false, TemplateEngine::get('non-existing-template-variable', false), 'passed default value is used if value is not known');
		$this->assertEquals($this, TemplateEngine::get('non-existing-template-variable', $this), 'complex default value is supported as default for unknown template variables');
		TemplateEngine::set('test-variable', true);
		$this->assertEquals(true, TemplateEngine::get('test-variable'), 'set template variable can be retrieved with get');
		TemplateEngine::delete('test-variable', true);
		$this->assertEquals(null, TemplateEngine::get('test-variable'), 'deleted template variable returns default value');
		TemplateEngine::set('test-variable', $this);
		$this->assertEquals($this, TemplateEngine::get('test-variable'), 'complex datatypes supported as template value');
	}

	public function testSetTitle() {
		$this->assertEquals(null, TemplateEngine::get('PAGE_TITLE'), 'page title is not set on initialization');
		TemplateEngine::setTitle('Unit Tests');
		$this->assertEquals('Unit Tests', TemplateEngine::get('PAGE_TITLE'), 'page title is set');
		$result = trim(TemplateEngine::processTemplate('te-core-test_title.tpl', false));
		$this->assertEquals('Unit Tests', $result, 'title available to templates as {PAGE_TITLE}');
	}

	public function testHeader() {
		$expect = '<meta name="generator" value="Unit Tests" />';
		$this->assertEquals(null, TemplateEngine::get('HEADER_TEXT'), 'HEADER_TEXT is undefined');
		TemplateEngine::header($expect);
		$this->assertEquals($expect, trim(TemplateEngine::get('HEADER_TEXT')), 'html for the HTML head is set');
		$result = trim(TemplateEngine::processTemplate('te-core-test_header.tpl', false));
		$this->assertEquals($expect, $result, 'HTML for the head section available to templates as {HEADER_TEXT}');
	}

	public function testAddCSS() {
		$expect = '<link type="text/css" rel="stylesheet" href="path/to/css.css" />';
		$this->assertEquals(null, TemplateEngine::get('HEADER_TEXT'), 'HEADER_TEXT is undefined');
		TemplateEngine::addCSS("path/to/css.css");
		$this->assertEquals($expect, trim(TemplateEngine::get('HEADER_TEXT')), 'link tag is correct');
		$result = trim(TemplateEngine::processTemplate('te-core-test_header.tpl', false));
		$this->assertEquals($expect, $result, 'link tag for the head section available to templates as {HEADER_TEXT}');
	}

	public function testAddJS() {
		$expect = '<script type="text/javascript" src="path/to/js.js" ></script>';
		$this->assertEquals(null, TemplateEngine::get('HEADER_TEXT'), 'HEADER_TEXT is undefined');
		TemplateEngine::addJS("path/to/js.js");
		$this->assertEquals($expect, trim(TemplateEngine::get('HEADER_TEXT')), 'script tag is correct');
		$result = trim(TemplateEngine::processTemplate('te-core-test_header.tpl', false));
		$this->assertEquals($expect, $result, 'script tag for the head section available to templates as {HEADER_TEXT}');
	}

	public function testTemplateEngineIsUnique() {
		$teInst = TemplateEngine::Inst();
		$tenew = new TemplateEngine();
		TemplateEngine::set('test-variable', true);
		$this->assertEquals(true, TemplateEngine::get('test-variable', false), 'test-variable available to the original instance');
		$this->assertEquals(true, $teInst->get('test-variable', false), 'test-variable available to the TemplateEngine::Inst() instance');
		$this->assertEquals(true, $tenew->get('test-variable', false), 'test-variable available to the TemplateEngine instance created with new');
	}

	private $TE_TEST_PLUGIN_called = false;
	private $TE_TEST_PLUGIN_DENY = false;

	public function TE_TEST_PLUGIN($context, $match) {
		$this->TE_TEST_PLUGIN_called = true;
		$this->assertEquals('{TE_TEST=success}', $match[0], 'matched directive looks as expected');
		$this->assertEquals('success', $match[1], 'match contains the expcted elements');
		$this->assertEquals(true, is_array($context), 'the context passed to the plugin is an array');
		$this->assertGreaterThan(0, count($context), 'the context contains variables');
		if ($this->TE_TEST_PLUGIN_DENY) {
			return false;
		}
		return $match[1];
	}

	public function testPluginRegistration() {
		$this->TE_TEST_PLUGIN_called = false;
		TemplateEngine::registerPlugin('TE_TEST_PLUGIN', '/\{TE_TEST=(success)\}/', array($this, 'TE_TEST_PLUGIN'));
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_test.tpl', false));
		$this->assertEquals(true, $this->TE_TEST_PLUGIN_called, 'plugin has been executed');
		$this->assertEquals('some content "success" all around', $result, 'custom template executed as expected');
		$this->TE_TEST_PLUGIN_called = false;
		$this->TE_TEST_PLUGIN_DENY = true;
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_test.tpl', false));
		$this->assertEquals(true, $this->TE_TEST_PLUGIN_called, 'plugin has been executed');
		$this->assertEquals('some content "{TE_TEST=success}" all around', $result, 'custom template not replaced (denied match)');
		$this->TE_TEST_PLUGIN_called = false;
		TemplateEngine::unregisterPlugin('TE_TEST_PLUGIN');
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_test.tpl', false));
		$this->assertEquals(false, $this->TE_TEST_PLUGIN_called, 'plugin has not been executed');
		$this->assertEquals('some content "{TE_TEST=success}" all around', $result, 'custom template not executed');
	}

	private $TE_TEST_ESCAPER_called = false;

	public function TE_TEST_ESCAPER($value, $config) {
		$this->TE_TEST_ESCAPER_called = true;
		$this->assertEquals($this, $config, 'escape method configuration passed to escape method');
		return "escaped";
	}

	public function testEscapeMethodRegistration() {
		$this->TE_TEST_ESCAPER_called = false;
		TemplateEngine::set('VARIABLE', 'original');
		TemplateEngine::registerEscapeMethod('TE_TEST_ESCAPER', array($this, 'TE_TEST_ESCAPER'));
		TemplateEngine::setEscapeMethodConfig('TE_TEST_ESCAPER', $this);
		$this->assertEquals($this, TemplateEngine::getEscapeMethodConfig('TE_TEST_ESCAPER'), 'escape method config correctly retrieved');
		$result = TemplateEngine::escape('TE_TEST_ESCAPER', 'original');
		$this->assertEquals(true, $this->TE_TEST_ESCAPER_called, 'escape method has been executed');
		$this->assertEquals('escaped', $result, 'escape method executed as expected');
		$this->TE_TEST_ESCAPER_called = false;
		TemplateEngine::unregisterEscapeMethod('TE_TEST_ESCAPER');
		$result = TemplateEngine::escape('TE_TEST_ESCAPER', 'original');
		$this->assertEquals(false, $this->TE_TEST_ESCAPER_called, 'escape method has not been executed');
		$this->assertEquals('original', $result, 'escape method not executed');
	}

	/**
	 * @expectedException TETemplateNotFoundException
	 */
	public function testMissingBasetemplateThrows() {
		TemplateEngine::processTemplate('non-existing-template-file.tpl', false);
	}

	/**
	 * @expectedException TETemplateNotFoundException
	 */
	public function testForceTPLExtensionThrows() {
		TemplateEngine::processTemplate('te-core-te_existing-template-file.txt', false);
	}

	/**
	 * @expectedException TETemplateNotFoundException
	 */
	public function testJailToTemplatePathThrows() {
		TemplateEngine::setTemplatePath('templates/jail-test');
		TemplateEngine::processTemplate('../te-core-te_existing-template-file-outside-template-path.tpl', false);
	}

	public function testBaseTemplateLookup() {
		/* RM */require_once('plugins/TE_LOAD.php');/* /RM */
		TemplateEngine::setBaseTemplatePath('templates/base-template');
		TemplateEngine::setMode(TEMode::debug);
		$result = trim(TemplateEngine::processTemplate('te-core-test-base-lookup.tpl', false));
		$this->assertEquals('Base: Normal', $result, 'base lookup failed');
	}

	public function testTemplateCacheBaseTemplateLookup() {
		/* RM */require_once('plugins/TE_LOAD.php');/* /RM */
		TemplateEngine::setBaseTemplatePath('templates/base-template');
		TemplateEngine::setMode(TEMode::debug);
		$result = trim(TemplateEngine::processTemplate('te-core-tpl-cache-base-template-interaction.tpl', false));
		$this->assertEquals('Base:tests/templates/base-template/;Sub:tests/templates/;#Base:tests/templates/base-template/;Sub:tests/templates/;', $result, 'template cache breaks TEMPLATE_PATH in cached templates');
	}

	public function testSubTemplateOverride() {
		/* RM */require_once('plugins/TE_LOAD.php');/* /RM */
		TemplateEngine::setBaseTemplatePath('templates/base-template');
		TemplateEngine::setMode(TEMode::debug);
		$result = trim(TemplateEngine::processTemplate('te-core-test-sub-override.tpl', false));
		$this->assertEquals('Sub:tests/templates/;Base:tests/templates/base-template/;Sub:tests/templates/;', $result, 'sub override failed');
	}
	public function TE_TEST_PLUGIN_PUSH_CONTEXT($context, $match) {
		return TemplateEngine :: pushContext('{' . $match[1] . '}', array($match[1] => 'context'));
	}

	public function testPluginPushContext() {
		TemplateEngine::registerPlugin('TE_TEST_PLUGIN_PUSH_CONTEXT', '/\{CONTEXT=(.*?)\}/', array($this, 'TE_TEST_PLUGIN_PUSH_CONTEXT'));
		TemplateEngine::set('TEST', 'base');
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_push_context_test.tpl', false));
		$this->assertEquals('from: context', $result, 'variable lookup using pushContext');
	}

	public function testEvents() {
		$args = array();
		TemplateEngine :: on('test_events_event', function($arg1, $arg2, $arg3) use (&$args) {
			$args['1'] = $arg1;
			$args['2'] = $arg2;
			$args['3'] = $arg3;
		});
		$result = TemplateEngine :: trigger('test_events_event', "aaa", $this, true);
		$this->assertEquals(3, count($args), "event handler has been called");
		$this->assertEquals("aaa", $args['1'], "arguments passed along");
		$this->assertEquals($this, $args['2'], "arguments passed along");
		$this->assertEquals(true, $args['3'], "arguments passed along");
	}
}

//EOF
