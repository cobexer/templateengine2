<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2011, Obexer Christoph
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: @DATE@
 * @author Obexer Christoph
 * @version @VERSION@ (@COMMIT@)
 * @package TemplateEngine2
 */
require_once('PEAR/PHPUnit/Autoload.php');

class TemplateEngineTestBase extends PHPUnit_Framework_TestCase
{
	protected function setUp() {
		require_once('TemplateEngine2.php');
		TemplateEngine::setRootPath(dirname(__FILE__));
		TemplateEngine::setTemplatePath('templates');
	}

	protected function tearDown() {
		TemplateEngine::clear();
	}

	public function testDisablePHPUnitWarning() {
	}
}

//EOF
