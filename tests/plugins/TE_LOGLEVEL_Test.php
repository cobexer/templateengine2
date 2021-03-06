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

class TE_LOGLEVEL_Test extends TemplateEngineTestBase
{
	protected function setUp() {
		parent::setUp();
		/* RM */require_once('plugins/TE_LOGLEVEL.php');/* /RM */
	}

	/**
	 * get_logs
	 * parse the html and find all the logs
	 * @param string $result html output
	 * @return array(array(class, message))
	 */
	private function get_logs($result) {
		$tree = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>' . $result);
		$logs = array();
		foreach($tree->xpath('//div/div') as $div) {
			$strong = $div->xpath('strong');
			$strong = $strong[0];
			$strongAttributes = $strong->attributes();
			$span = $div->xpath('span[@class="te_msg_text"]');
			$logs[] = array((string)($strongAttributes['class']), (string)($span[0]));
		}
		return $logs;
	}

	public function testDefaultLogLevel() {
		TemplateEngine::LogMsg("MY_NONE", true, TEMode::none, true);
		TemplateEngine::LogMsg("MY_DEBUG", true, TEMode::debug, true);
		TemplateEngine::LogMsg("MY_WARNING", true, TEMode::warning, true);
		TemplateEngine::LogMsg("MY_ERROR", true, TEMode::error, true);
		$logs = $this->get_logs(TemplateEngine::processTemplate('plugins/TE_LOGLEVEL/logs-default.tpl', false));

		$this->assertEquals(2, count($logs), 'default loglevel is error, thus only none and error appear in the result');
		$this->assertEquals('te_msg_non', $logs[0][0], 'first message type is none');
		$this->assertEquals('MY_NONE', $logs[0][1], 'message text unmodified');
		$this->assertEquals('te_msg_err', $logs[1][0], 'second message type is error');
		$this->assertEquals('MY_ERROR', $logs[1][1], 'message text unmodified');
	}

	public function testErrorLogLevel() {
		TemplateEngine::LogMsg("MY_NONE", true, TEMode::none, true);
		TemplateEngine::LogMsg("MY_DEBUG", true, TEMode::debug, true);
		TemplateEngine::LogMsg("MY_WARNING", true, TEMode::warning, true);
		TemplateEngine::LogMsg("MY_ERROR", true, TEMode::error, true);
		$logs = $this->get_logs(TemplateEngine::processTemplate('plugins/TE_LOGLEVEL/logs-error.tpl', false));

		$this->assertEquals(2, count($logs), 'loglevel error, thus only none and error appear in the result');
		$this->assertEquals('te_msg_non', $logs[0][0], 'first message type is none');
		$this->assertEquals('MY_NONE', $logs[0][1], 'message text unmodified');
		$this->assertEquals('te_msg_err', $logs[1][0], 'second message type is error');
		$this->assertEquals('MY_ERROR', $logs[1][1], 'message text unmodified');
	}

	public function testWarningLogLevel() {
		TemplateEngine::LogMsg("MY_NONE", true, TEMode::none, true);
		TemplateEngine::LogMsg("MY_DEBUG", true, TEMode::debug, true);
		TemplateEngine::LogMsg("MY_WARNING", true, TEMode::warning, true);
		TemplateEngine::LogMsg("MY_ERROR", true, TEMode::error, true);
		$logs = $this->get_logs(TemplateEngine::processTemplate('plugins/TE_LOGLEVEL/logs-warning.tpl', false));

		$this->assertEquals(3, count($logs), 'loglevel warning, thus only none, warning and error appear in the result');
		$this->assertEquals('te_msg_non', $logs[0][0], 'first message type is none');
		$this->assertEquals('MY_NONE', $logs[0][1], 'message text unmodified');
		$this->assertEquals('te_msg_wrn', $logs[1][0], 'second message type is warning');
		$this->assertEquals('MY_WARNING', $logs[1][1], 'message text unmodified');
		$this->assertEquals('te_msg_err', $logs[2][0], 'third message type is error');
		$this->assertEquals('MY_ERROR', $logs[2][1], 'message text unmodified');
	}

	public function testDebugLogLevel() {
		TemplateEngine::LogMsg("MY_NONE", true, TEMode::none, true);
		TemplateEngine::LogMsg("MY_DEBUG", true, TEMode::debug, true);
		TemplateEngine::LogMsg("MY_WARNING", true, TEMode::warning, true);
		TemplateEngine::LogMsg("MY_ERROR", true, TEMode::error, true);
		$logs = $this->get_logs(TemplateEngine::processTemplate('plugins/TE_LOGLEVEL/logs-debug.tpl', false));

		$this->assertGreaterThan(4, count($logs), 'loglevel debug, thus all messages appear in the result');
		$this->assertEquals('te_msg_non', $logs[0][0], 'first message type is none');
		$this->assertEquals('MY_NONE', $logs[0][1], 'message text unmodified');
		$this->assertEquals('te_msg_dbg', $logs[1][0], 'second message type is debug');
		$this->assertEquals('MY_DEBUG', $logs[1][1], 'message text unmodified');
		$this->assertEquals('te_msg_wrn', $logs[2][0], 'third message type is warning');
		$this->assertEquals('MY_WARNING', $logs[2][1], 'message text unmodified');
		$this->assertEquals('te_msg_err', $logs[3][0], 'fourth message type is error');
		$this->assertEquals('MY_ERROR', $logs[3][1], 'message text unmodified');
	}

	public function testNoneLogLevel() {
		TemplateEngine::LogMsg("MY_NONE", true, TEMode::none, true);
		TemplateEngine::LogMsg("MY_DEBUG", true, TEMode::debug, true);
		TemplateEngine::LogMsg("MY_WARNING", true, TEMode::warning, true);
		TemplateEngine::LogMsg("MY_ERROR", true, TEMode::error, true);
		$logs = $this->get_logs(TemplateEngine::processTemplate('plugins/TE_LOGLEVEL/logs-none.tpl', false));

		$this->assertEquals(1, count($logs), 'loglevel none, thus only none appears in the result');
		$this->assertEquals('te_msg_non', $logs[0][0], 'first message type is none');
		$this->assertEquals('MY_NONE', $logs[0][1], 'message text unmodified');
	}

	public function testInvalidLoglevel() {
		$result = str_replace("\r\n", "\n", trim(TemplateEngine::processTemplate('plugins/TE_LOGLEVEL/logs-invalid.tpl', false)));
		$this->assertEquals("<body>\n{LOGLEVEL=WTF}\n</body>", $result, 'invalid loglevel ignored');
	}
}
