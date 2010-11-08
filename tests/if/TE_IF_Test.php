<?php

require_once('PEAR/PHPUnit/Autoload.php');
require_once('TemplateEngine2.php');

class TE_IFTest extends PHPUnit_Framework_TestCase
{
	protected function setUp() {
		TemplateEngine::setRootPath(dirname(__FILE__));
		TemplateEngine::setTemplatePath('.');
	}

	protected function tearDown() {
		TemplateEngine::clear();
	}

	public function testVarBoolean() {
		TemplateEngine::set('VARBOOL', true);
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('te-if-bool.tpl', false)));
		TemplateEngine::set('VARBOOL', false);
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('te-if-bool.tpl', false)));
	}

	public function testVarText() {
		TemplateEngine::set('VARTEXT', "some_text");
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('te-if-string.tpl', false)));
		TemplateEngine::set('VARTEXT', "some_other_text");
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('te-if-string.tpl', false)));
	}

	public function testVarNumber() {
		TemplateEngine::set('VARNUMBER', 42);
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('te-if-number.tpl', false)));
		TemplateEngine::set('VARNUMBER', 1337);
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('te-if-number.tpl', false)));
	}
}


//EOF
