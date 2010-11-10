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
require_once(dirname(__FILE__) . '/../TemplateEngineTestBase.php');

class TE_IFTest extends TemplateEngineTestBase
{
	public function testVarBoolean() {
		TemplateEngine::set('VARBOOL', true);
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('if/bool.tpl', false)));
		TemplateEngine::set('VARBOOL', false);
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('if/bool.tpl', false)));
	}

	public function testVarText() {
		TemplateEngine::set('VARTEXT', "some_text");
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('if/string.tpl', false)));
		TemplateEngine::set('VARTEXT', "some_other_text");
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('if/string.tpl', false)));
	}

	public function testVarNumber() {
		TemplateEngine::set('VARNUMBER', 42);
		$this->assertEquals("true", trim(TemplateEngine::processTemplate('if/number.tpl', false)));
		TemplateEngine::set('VARNUMBER', 1337);
		$this->assertEquals("false", trim(TemplateEngine::processTemplate('if/number.tpl', false)));
	}
}


//EOF
