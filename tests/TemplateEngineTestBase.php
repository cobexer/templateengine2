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

class TemplateEngineTestBase extends PHPUnit_Framework_TestCase
{
	protected function setUp() {
		require_once('TemplateEngine2.php');
		TemplateEngine::setRootPath('tests');
		TemplateEngine::setTemplatePath('templates');
	}

	protected function tearDown() {
		TemplateEngine::clear();
	}
}

//EOF
