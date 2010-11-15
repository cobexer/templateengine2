<?php
/**
 * TemplateEngine2 PHP Templating System $VERSION$
 * http://gruewo.dyndns.org/gitweb/?p=templateengine2.git
 *
 * @copyright Copyright 2010, Obexer Christoph
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: $DATE$
 * @author Obexer Christoph
 * @version $VERSION$
 * @package TemplateEngine2
 */
require_once(dirname(__FILE__) . '/TemplateEngineTestBase.php');

class TemplateEngineCoreTest extends TemplateEngineTestBase
{
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
		$this->assertEquals(true, is_array($context), 'the contextt passed to the plugin is an array');
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
		$this->assertEquals(false, $this->TE_TEST_PLUGIN_called, 'plugin has been executed');
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_test.tpl', false));
		$this->assertEquals('some content "{TE_TEST=success}" all around', $result, 'custom template not executed');
	}
}

//EOF
