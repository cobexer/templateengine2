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

class TemplateEngineCoreInitTest extends TemplateEngineTestBase
{
	//overwrite setUp/tearDown to avoid TemplateEngine inclusion
	protected function setUp() {
	}

	protected function tearDown() {
	}

	public function testInitialization() {
		$this->assertEquals(false, class_exists('TemplateEngine', false), 'TemplateEngine class not yet defined');
		include('TemplateEngine2.php');
		$this->assertEquals(true, class_exists('TemplateEngine', false), 'TemplateEngine class defined');
		$this->assertEquals(true, is_array(TemplateEngine::get('TE_ERRORS', null)), 'TE_ERRORS is set and an array');
		$this->assertEquals(0, count(TemplateEngine::get('TE_ERRORS', null)), 'TE_ERRORS is of length 0');
		$this->assertEquals(true, is_array(TemplateEngine::get('TE_WARNINGS', null)), 'TE_WARNINGS is set and an array');
		$this->assertEquals(0, count(TemplateEngine::get('TE_WARNINGS', null)), 'TE_WARNINGS is of length 0');
		$this->assertEquals(true, is_array(TemplateEngine::get('TE_INFOS', null)), 'TE_INFOS is set and an array');
		$this->assertEquals(0, count(TemplateEngine::get('TE_INFOS', null)), 'TE_INFOS is of length 0');
		$this->assertEquals(true, is_string(TemplateEngine::get('HEADER_TEXT', null)), 'HEADER_TEXT is of set and a string');
		$this->assertEquals("", TemplateEngine::get('HEADER_TEXT', null), 'HEADER_TEXT is empty');
	}
}

//EOF
