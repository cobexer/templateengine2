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
require_once(dirname(__FILE__) . '/../TemplateEngineTestBase.php');

class TE_LOGLEVEL_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_LOGLEVEL.php');/* /RM */
	}

	private function get_logs($result) {
		$tree = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>' . $result);
		$logs = array();
		foreach($tree->xpath('//div/div') as $div) {
			$logs[] = array((string)($div->xpath('strong')[0]->attributes()['class']), (string)($div->xpath('span[@class="te_msg_text"]')[0]));
		}
		return $logs;
	}

	public function testDefaultLogLevel() {
		TemplateEngine :: LogMsg("MY_NONE", true, TEMode :: none, true);
		TemplateEngine :: LogMsg("MY_DEBUG", true, TEMode :: debug, true);
		TemplateEngine :: LogMsg("MY_WARNING", true, TEMode :: warning, true);
		TemplateEngine :: LogMsg("MY_ERROR", true, TEMode :: error, true);
		$logs = $this->get_logs(TemplateEngine :: processTemplate('plugins/TE_LOGLEVEL/logs.tpl', false));

		$this->assertEquals(2, count($logs), 'default loglevel is error, thus only none and error appear in the result');
		$this->assertEquals('te_msg_non', $logs[0][0], 'first message typ is none');
		$this->assertEquals('MY_NONE', $logs[0][1], 'message text unmodified');
		$this->assertEquals('te_msg_err', $logs[1][0], 'second message typ is error');
		$this->assertEquals('MY_ERROR', $logs[1][1], 'message text unmodified');
	}
}
