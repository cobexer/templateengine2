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
require_once(dirname(__FILE__) . '/../TemplateEngineTestBase.php');

class TE_LOAD_WITHID_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_LOAD_WITHID.php');/* /RM */
	}

	public function testLoadFile() {
		$result = trim(TemplateEngine::processTemplate('plugins/TE_LOAD_WITHID/load-template.tpl', false));
		$this->assertEquals('succeeded (test-id)', $result, 'template loaded');
	}

	public function testLoadHonoursForcedTplExtension() {
		TemplateEngine::setTemplatePath('templates/plugins/TE_LOAD_WITHID/');
		$result = trim(TemplateEngine::processTemplate('template-extension.tpl', false));
		$this->assertEquals('{LOAD_WITHID=template-extension.css;test-id}', $result, 'load failed -> original template code stays');
	}

	public function testLoadHonoursJailToTemplatePath() {
		TemplateEngine::setTemplatePath('templates/plugins/TE_LOAD_WITHID/');
		$result = trim(TemplateEngine::processTemplate('template-jail.tpl', false));
		$this->assertEquals('{LOAD_WITHID=../te-core-te_existing-template-file-outside-template-path.tpl;test-id}', $result, 'load failed -> original template code stays');
	}
}
