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
require_once('PEAR/PHPUnit/Autoload.php');

class TemplateEngineCoreTest extends PHPUnit_Framework_TestCase
{
	protected function setUp() {
		require_once('TemplateEngine2.php');
		TemplateEngine::setRootPath(dirname(__FILE__));
		TemplateEngine::setTemplatePath('.');
	}

	protected function tearDown() {
		TemplateEngine::clear();
	}

	public function testSetRootPath() {
		TemplateEngine::setRootPath(dirname(__FILE__));
		$this->assertEquals(dirname(__FILE__) . '/', TemplateEngine::getRootPath(), 'set root path gets / added at the end');
		TemplateEngine::setTemplatePath('.');
		$this->assertEquals(dirname(__FILE__) . '/', trim(TemplateEngine::processTemplate('te-core-rootpath.tpl', false)), 'root path available to templates');
	}
}

//EOF
