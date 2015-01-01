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

class TE_ASSET_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_ASSET.php');/* /RM */
	}

	public function testAssetLookup() {
		$result = trim(TemplateEngine::processTemplate('plugins/TE_ASSET/normal.tpl', false));
		$this->assertEquals('tests/templates/plugins/TE_ASSET/normal.css', $result, 'normal asset lookup in templatePath');
	}

	public function testBaseAssetLookup() {
		TemplateEngine::setBaseTemplatePath('templates/base-template');
		$result = trim(TemplateEngine::processTemplate('plugins/TE_ASSET/base.tpl', false));
		$this->assertEquals('tests/templates/base-template/plugins/TE_ASSET/base.css', $result, 'asset lookup in baseTemplatePath');
	}

	public function testAssetHonoursJailToTemplatePath() {
		TemplateEngine::setTemplatePath('templates/plugins/TE_ASSET/');
		$result = trim(TemplateEngine::processTemplate('jail.tpl', false));
		$this->assertEquals('{ASSET:../../te-asset-existing-template-file-outside-template-path.tpl}', $result, 'asset lookup fails (because it is denied), original code stays');
	}
}
