<?php
/**
 * TemplateEngine2 PHP Templating System @VERSION@
 * @WWW@
 *
 * @copyright Copyright 2010-2012, Obexer Christoph
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: @DATE@
 * @author Obexer Christoph
 * @version @VERSION@ (@COMMIT@)
 * @package TemplateEngine2
 */
require_once(dirname(__FILE__) . '/TemplateEngineTestBase.php');

class TemplateEngineCoverageBaseTest extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		$plugins = glob('plugins/*.php');
		foreach($plugins as $plugin) {
			/* RM */require_once($plugin);/* /RM */
		}
	}
}

//EOF
