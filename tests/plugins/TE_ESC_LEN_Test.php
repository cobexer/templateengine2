<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2010, Obexer Christoph
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
		require_once('plugins/TE_ESC_LEN.php');
	}
	//FIXME: add tests here
}
