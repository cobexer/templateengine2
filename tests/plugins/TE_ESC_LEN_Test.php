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

class TE_ESC_LEN_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_ESC_LEN.php');/* /RM */
	}

	public function testString() {
		$this->assertEquals(0, TemplateEngine::escape('LEN', ""), "LEN escape method returns 0 for empty string");
		$this->assertEquals(5, TemplateEngine::escape('LEN', "World"), "LEN escape method returns 5 for 'World'");
	}

	public function testArray() {
		$this->assertEquals(0, TemplateEngine::escape('LEN', array()), "LEN escape method returns 0 for empty array");
		$this->assertEquals(3, TemplateEngine::escape('LEN', array('a', 'b', 'c')), "LEN escape method returns 3 for array {a, b, c}");
	}

	public function testInvalid() {
		$this->assertEquals(0, TemplateEngine::escape('LEN', null), "LEN escape method returns 0 for null");
		$this->assertEquals(0, TemplateEngine::escape('LEN', $this), "LEN escape method returns 0 for \$this");
		$this->assertEquals(0, TemplateEngine::escape('LEN', 1337), "LEN escape method returns 0 for 1337");
	}
}
