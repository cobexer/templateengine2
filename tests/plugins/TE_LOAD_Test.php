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
require_once(dirname(__FILE__) . '/../TemplateEngineTestBase.php');

class TE_LOAD_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_LOAD.php');/* /RM */
	}

	public function testLoadFile() {
		$result = trim(TemplateEngine::processTemplate('plugins/TE_LOAD/load-template.tpl', false));
		$this->assertEquals('succeeded', $result, 'template loaded');
	}

	public function testLoadHonoursForcedTplExtension() {
		TemplateEngine::setTemplatePath('templates/plugins/TE_LOAD/');
		$result = trim(TemplateEngine::processTemplate('template-extension.tpl', false));
		$this->assertEquals('{LOAD=template-extension.css}', $result, 'load failed -> original template code stays');
	}

	public function testLoadHonoursJailToTemplatePath() {
		TemplateEngine::setTemplatePath('templates/plugins/TE_LOAD/');
		$result = trim(TemplateEngine::processTemplate('template-jail.tpl', false));
		$this->assertEquals('{LOAD=../te-core-te_existing-template-file-outside-template-path.tpl}', $result, 'load failed -> original template code stays');
	}
}
