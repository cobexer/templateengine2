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
require_once(dirname(__FILE__) . '/TemplateEngineTestBase.php');

class TemplateEngineCoreTest extends TemplateEngineTestBase
{
	protected function setUp() {
		// required for testPrintTimingStatistics
		$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
		parent::setUp();
		/* RM */require_once('plugins/TE_SCALAR.php');/* /RM */
	}

	public function testSetRootPath() {
		TemplateEngine::setRootPath(dirname(__FILE__));
		$this->assertEquals(dirname(__FILE__) . '/', TemplateEngine::getRootPath(), 'set root path gets / added at the end');
		TemplateEngine::setTemplatePath('templates');
		$this->assertEquals(dirname(__FILE__) . '/', trim(TemplateEngine::processTemplate('te-core-rootpath.tpl', false)), 'root path available to templates');
	}

	public function testSetTemplatePath() {
		TemplateEngine::setTemplatePath('coverage-templates');
		$this->assertEquals('coverage-templates/', TemplateEngine::getTemplatePath(), 'set template path gets / added at the end');
		TemplateEngine::setTemplatePath('templates');
		$this->assertEquals(TemplateEngine::getRootPath() . TemplateEngine::getTemplatePath(), trim(TemplateEngine::processTemplate('te-core-templatepath.tpl', false)), 'template path available to templates');
	}

	public function testSetAndGet() {
		$this->assertEquals(null, TemplateEngine::get('non-existing-template-variable'), 'default for unknown variables is null');
		$this->assertEquals(false, TemplateEngine::get('non-existing-template-variable', false), 'passed default value is used if value is not known');
		$this->assertEquals($this, TemplateEngine::get('non-existing-template-variable', $this), 'complex default value is supported as default for unknown template variables');
		TemplateEngine::set('test-variable', true);
		$this->assertEquals(true, TemplateEngine::get('test-variable'), 'set template variable can be retrieved with get');
		TemplateEngine::delete('test-variable', true);
		$this->assertEquals(null, TemplateEngine::get('test-variable'), 'deleted template variable returns default value');
		TemplateEngine::set('test-variable', $this);
		$this->assertEquals($this, TemplateEngine::get('test-variable'), 'complex datatypes supported as template value');
	}

	public function testSetTitle() {
		$this->assertEquals(null, TemplateEngine::get('PAGE_TITLE'), 'page title is not set on initialization');
		TemplateEngine::setTitle('Unit Tests');
		$this->assertEquals('Unit Tests', TemplateEngine::get('PAGE_TITLE'), 'page title is set');
		$result = trim(TemplateEngine::processTemplate('te-core-test_title.tpl', false));
		$this->assertEquals('Unit Tests', $result, 'title available to templates as {PAGE_TITLE}');
	}

	public function testHeader() {
		$expect = '<meta name="generator" value="Unit Tests" />';
		$this->assertEquals(null, TemplateEngine::get('HEADER_TEXT'), 'HEADER_TEXT is undefined');
		TemplateEngine::header($expect);
		$this->assertEquals($expect, trim(TemplateEngine::get('HEADER_TEXT')), 'html for the HTML head is set');
		$result = trim(TemplateEngine::processTemplate('te-core-test_header.tpl', false));
		$this->assertEquals($expect, $result, 'HTML for the head section available to templates as {HEADER_TEXT}');
	}

	public function testAddCSS() {
		$expect = '<link type="text/css" rel="stylesheet" href="path/to/css.css" />';
		$this->assertEquals(null, TemplateEngine::get('HEADER_TEXT'), 'HEADER_TEXT is undefined');
		TemplateEngine::addCSS("path/to/css.css");
		$this->assertEquals($expect, trim(TemplateEngine::get('HEADER_TEXT')), 'link tag is correct');
		$result = trim(TemplateEngine::processTemplate('te-core-test_header.tpl', false));
		$this->assertEquals($expect, $result, 'link tag for the head section available to templates as {HEADER_TEXT}');
	}

	public function testAddJS() {
		$expect = '<script type="text/javascript" src="path/to/js.js"></script>';
		$this->assertEquals(null, TemplateEngine::get('HEADER_TEXT'), 'HEADER_TEXT is undefined');
		TemplateEngine::addJS("path/to/js.js");
		$this->assertEquals($expect, trim(TemplateEngine::get('HEADER_TEXT')), 'script tag is correct');
		$result = trim(TemplateEngine::processTemplate('te-core-test_header.tpl', false));
		$this->assertEquals($expect, $result, 'script tag for the head section available to templates as {HEADER_TEXT}');
	}

	public function testTemplateEngineIsUnique() {
		$teInst = TemplateEngine::Inst();
		TemplateEngine::set('test-variable', true);
		$tenew = new TemplateEngine();
		$this->assertEquals(true, TemplateEngine::get('test-variable', false), 'test-variable available to the original instance');
		$this->assertEquals(true, $teInst->get('test-variable', false), 'test-variable available to the TemplateEngine::Inst() instance');
		$this->assertEquals(true, $tenew->get('test-variable', false), 'test-variable available to the TemplateEngine instance created with new');
	}

	private $TE_TEST_PLUGIN_called = false;
	private $TE_TEST_PLUGIN_DENY = false;
	private $TE_TEST_PLUGIN_DO_UNREGISTER = true;

	public function TE_TEST_PLUGIN($context, $match) {
		$this->TE_TEST_PLUGIN_called = true;
		$this->assertEquals('{TE_TEST=success}', $match[0], 'matched directive looks as expected');
		$this->assertEquals('success', $match[1], 'match contains the expcted elements');
		$this->assertEquals(true, is_array($context), 'the context passed to the plugin is an array');
		$this->assertGreaterThan(0, count($context), 'the context contains variables');
		if ($this->TE_TEST_PLUGIN_DENY) {
			return false;
		}
		return $match[1];
	}

	public function testPluginRegistration() {
		$this->TE_TEST_PLUGIN_called = false;
		TemplateEngine::registerPlugin('TE_TEST_PLUGIN', '/\{TE_TEST=(success)\}/', array($this, 'TE_TEST_PLUGIN'));
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_test.tpl', false));
		$this->assertEquals(true, $this->TE_TEST_PLUGIN_called, 'plugin has been executed');
		$this->assertEquals('some content "success" all around', $result, 'custom template executed as expected');
		$this->TE_TEST_PLUGIN_called = false;
		$this->TE_TEST_PLUGIN_DENY = true;
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_test.tpl', false));
		$this->assertEquals(true, $this->TE_TEST_PLUGIN_called, 'plugin has been executed');
		$this->assertEquals('some content "{TE_TEST=success}" all around', $result, 'custom template not replaced (denied match)');
		$this->TE_TEST_PLUGIN_called = false;
		if ($this->TE_TEST_PLUGIN_DO_UNREGISTER) {
			TemplateEngine::unregisterPlugin('TE_TEST_PLUGIN');
			$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_test.tpl', false));
			$this->assertEquals(false, $this->TE_TEST_PLUGIN_called, 'plugin has not been executed');
			$this->assertEquals('some content "{TE_TEST=success}" all around', $result, 'custom template not executed');
		}
	}

	public function testPluginStatistics() {
		TemplateEngine :: option('plugin_profiling', true);
		$this->TE_TEST_PLUGIN_DO_UNREGISTER = false;
		$this->testPluginRegistration();
		$total = array(
			'hit' => 0,
			'try' => 0,
			'decline' => 0,
			'regex_time' => 0,
		);
		$stats = TemplateEngine :: getPluginStatistics();
		foreach($stats as $stat) {
			$total['hit'] += $stat['hit'];
			$total['try'] += $stat['try'];
			$total['decline'] += $stat['decline'];
			$total['regex_time'] += $stat['regex_time'];
		}
		$this->assertGreaterThan(0, $total['hit'], 'total hits');
		$this->assertGreaterThan(0, $total['try'], 'total tries');
		$this->assertGreaterThan(0, $total['decline'], 'total declines');
		$this->assertGreaterThan(0, $total['regex_time'], 'total regex_time');
	}

	private $TE_TEST_ESCAPER_called = false;

	public function TE_TEST_ESCAPER($value, $config) {
		$this->TE_TEST_ESCAPER_called = true;
		$this->assertEquals($this, $config, 'escape method configuration passed to escape method');
		return "escaped";
	}

	public function testEscapeMethodRegistration() {
		$this->TE_TEST_ESCAPER_called = false;
		TemplateEngine::set('VARIABLE', 'original');
		TemplateEngine::registerEscapeMethod('TE_TEST_ESCAPER', array($this, 'TE_TEST_ESCAPER'));
		TemplateEngine::setEscapeMethodConfig('TE_TEST_ESCAPER', $this);
		$this->assertEquals($this, TemplateEngine::getEscapeMethodConfig('TE_TEST_ESCAPER'), 'escape method config correctly retrieved');
		$result = TemplateEngine::escape('TE_TEST_ESCAPER', 'original');
		$this->assertEquals(true, $this->TE_TEST_ESCAPER_called, 'escape method has been executed');
		$this->assertEquals('escaped', $result, 'escape method executed as expected');
		$this->TE_TEST_ESCAPER_called = false;
		TemplateEngine::unregisterEscapeMethod('TE_TEST_ESCAPER');
		$result = TemplateEngine::escape('TE_TEST_ESCAPER', 'original');
		$this->assertEquals(false, $this->TE_TEST_ESCAPER_called, 'escape method has not been executed');
		$this->assertEquals('original', $result, 'escape method not executed');
	}

	/**
	 * @expectedException TEPluginRegexInvalidException
	 */
	public function testInvalidPluginRegex() {
		TemplateEngine :: registerPlugin('invalidRegexPlugin', '/[/', function() {});
	}

	/**
	 * @expectedException TEPluginCallbackInvalidException
	 */
	public function testNonexistentFunction() {
		TemplateEngine :: registerPlugin('undefinedFunctionPlugin', '/./', 'letsJustHopeAFunctionWithThisNameIsNeverDefined');
	}

	/**
	 * @expectedException TEPluginCallbackInvalidException
	 */
	public function testNonexistentMemberFunction() {
		TemplateEngine :: registerPlugin('undefinedMemberFunctionPlugin', '/./', array($this, 'letsJustHopeAMemberFunctionWithThisNameIsNeverDefined'));
	}

	/**
	 * @expectedException TEPluginCallbackInvalidException
	 */
	public function testInvalidCallbackArgument() {
		TemplateEngine :: registerPlugin('undefinedInvalidCallbackArgument', '/./', array(null));
	}

	/**
	 * @expectedException TETemplateNotFoundException
	 */
	public function testMissingBasetemplateThrows() {
		TemplateEngine::processTemplate('non-existing-template-file.tpl', false);
	}

	/**
	 * @expectedException TETemplateNotFoundException
	 */
	public function testForceTPLExtensionThrows() {
		TemplateEngine::processTemplate('te-core-te_existing-template-file.txt', false);
	}

	/**
	 * @expectedException TETemplateNotFoundException
	 */
	public function testJailToTemplatePathThrows() {
		TemplateEngine::setTemplatePath('templates/jail-test');
		TemplateEngine::processTemplate('../te-core-te_existing-template-file-outside-template-path.tpl', false);
	}

	public function testBaseTemplateLookup() {
		/* RM */require_once('plugins/TE_LOAD.php');/* /RM */
		TemplateEngine::setBaseTemplatePath('templates/base-template');
		TemplateEngine::setMode(TEMode::debug);
		$result = trim(TemplateEngine::processTemplate('te-core-test-base-lookup.tpl', false));
		$this->assertEquals('Base: Normal', $result, 'base lookup failed');
	}

	public function testTemplateCacheBaseTemplateLookup() {
		/* RM */require_once('plugins/TE_LOAD.php');/* /RM */
		TemplateEngine::setBaseTemplatePath('templates/base-template');
		TemplateEngine::setMode(TEMode::debug);
		$result = trim(TemplateEngine::processTemplate('te-core-tpl-cache-base-template-interaction.tpl', false));
		$this->assertEquals('Base:tests/templates/base-template/;Sub:tests/templates/;#Base:tests/templates/base-template/;Sub:tests/templates/;', $result, 'template cache breaks TEMPLATE_PATH in cached templates');
	}

	public function testSubTemplateOverride() {
		/* RM */require_once('plugins/TE_LOAD.php');/* /RM */
		TemplateEngine::setBaseTemplatePath('templates/base-template');
		TemplateEngine::setMode(TEMode::debug);
		$result = trim(TemplateEngine::processTemplate('te-core-test-sub-override.tpl', false));
		$this->assertEquals('Sub:tests/templates/;Base:tests/templates/base-template/;Sub:tests/templates/;', $result, 'sub override failed');
	}
	public function TE_TEST_PLUGIN_PUSH_CONTEXT($context, $match) {
		return TemplateEngine :: pushContext('{' . $match[1] . '}', array($match[1] => 'context'));
	}

	public function testPluginPushContext() {
		TemplateEngine::registerPlugin('TE_TEST_PLUGIN_PUSH_CONTEXT', '/\{CONTEXT=(.*?)\}/', array($this, 'TE_TEST_PLUGIN_PUSH_CONTEXT'));
		TemplateEngine::set('TEST', 'base');
		$result = trim(TemplateEngine::processTemplate('te-core-te_plugin_push_context_test.tpl', false));
		$this->assertEquals('from: context', $result, 'variable lookup using pushContext');
	}

	public function testEvents() {
		$args = array();
		TemplateEngine :: on('test_events_event', function($arg1, $arg2, $arg3) use (&$args) {
			$args['1'] = $arg1;
			$args['2'] = $arg2;
			$args['3'] = $arg3;
		});
		$result = TemplateEngine :: trigger('test_events_event', "aaa", $this, true);
		$this->assertEquals(true, $result, "event has not been cancelled");
		$this->assertEquals(3, count($args), "event handler has been called");
		$this->assertEquals("aaa", $args['1'], "arguments passed along");
		$this->assertEquals($this, $args['2'], "arguments passed along");
		$this->assertEquals(true, $args['3'], "arguments passed along");
	}

	public function testSetOption() {
		$eval = 0;
		$execute = 0;
		$inform = 0;
		$callbackInvocation = 0;
		$this->assertEquals(true, TemplateEngine :: option('gzip'), 'gzip option turned on');
		$argsEval = array();
		$argsExecute = array();
		$argsInform = array();
		TemplateEngine :: on('set_option', function($name, $value) use (&$argsEval, &$eval, &$callbackInvocation) {
			$argsEval['name'] = $name;
			$argsEval['value'] = $value;
			$eval = ++$callbackInvocation;
		}, TEEventPhase :: evaluate);
		TemplateEngine :: on('set_option', function($name, $value) use (&$argsExecute, &$execute, &$callbackInvocation) {
			$argsExecute['name'] = $name;
			$argsExecute['value'] = $value;
			$execute = ++$callbackInvocation;
		}, TEEventPhase :: execute);
		TemplateEngine :: on('set_option', function($name, $value) use (&$argsInform, &$inform, &$callbackInvocation) {
			$argsInform['name'] = $name;
			$argsInform['value'] = $value;
			$inform = ++$callbackInvocation;
		}, TEEventPhase :: inform);
		TemplateEngine :: option('gzip', false);
		$this->assertEquals(2, count($argsEval), "event handler has been called");
		$this->assertEquals("gzip", $argsEval['name'], "option name passed along");
		$this->assertEquals(false, $argsEval['value'], "new option value passed along");
		$this->assertEquals(1, $eval, "eval event handler has been called first");

		$this->assertEquals(2, count($argsExecute), "event handler has been called");
		$this->assertEquals("gzip", $argsExecute['name'], "option name passed along");
		$this->assertEquals(false, $argsExecute['value'], "new option value passed along");
		$this->assertEquals(2, $execute, "execute event handler has been called second");

		$this->assertEquals(2, count($argsInform), "event handler has been called");
		$this->assertEquals("gzip", $argsInform['name'], "option name passed along");
		$this->assertEquals(false, $argsInform['value'], "new option value passed along");
		$this->assertEquals(3, $inform, "inform event handler has been called third");

		$this->assertEquals(false, TemplateEngine :: option('gzip'), 'gzip option turned off');
	}

	public function testInhibitSetOption() {
		$eval = 0;
		$execute = 0;
		$inform = 0;
		$callbackInvocation = 0;
		$this->assertEquals(true, TemplateEngine :: option('gzip'), 'gzip option turned on');
		$argsEval = array();
		$argsExecute = array();
		$argsInform = array();
		TemplateEngine :: on('set_option', function($name, $value) use (&$argsEval, &$eval, &$callbackInvocation) {
			$argsEval['name'] = $name;
			$argsEval['value'] = $value;
			$eval = ++$callbackInvocation;
			return false;
		}, TEEventPhase :: evaluate);
		TemplateEngine :: on('set_option', function($name, $value) use (&$argsExecute, &$execute, &$callbackInvocation) {
			$argsExecute['name'] = $name;
			$argsExecute['value'] = $value;
			$execute = ++$callbackInvocation;
		}, TEEventPhase :: execute);
		TemplateEngine :: on('set_option', function($name, $value) use (&$argsInform, &$inform, &$callbackInvocation) {
			$argsInform['name'] = $name;
			$argsInform['value'] = $value;
			$inform = ++$callbackInvocation;
		}, TEEventPhase :: inform);
		TemplateEngine :: option('gzip', false);
		$this->assertEquals(2, count($argsEval), "event handler has been called");
		$this->assertEquals("gzip", $argsEval['name'], "option name passed along");
		$this->assertEquals(false, $argsEval['value'], "new option value passed along");
		$this->assertEquals(1, $eval, "eval event handler has been called first");

		$this->assertEquals(0, count($argsExecute), "event handler has been called");
		$this->assertEquals(0, $execute, "execute event handler has been called second");

		$this->assertEquals(0, count($argsInform), "event handler has been called");
		$this->assertEquals(0, $inform, "inform event handler has been called third");

		$this->assertEquals(true, TemplateEngine :: option('gzip'), 'gzip option still turned on');
	}

	public function testStaticInitEvent() {
		TemplateEngine :: set('static_init', '');
		TemplateEngine :: set('init', '');
		$this->assertEquals('', TemplateEngine::get('static_init'), 'static_init is empty');
		$this->assertEquals('', TemplateEngine::get('init'), 'init is empty');
		TemplateEngine :: on('static_init', function() {
			TemplateEngine :: set('static_init', 'yes');
		});
		TemplateEngine :: on('init', function() {
			TemplateEngine :: set('init', 'yes');
		});
		$this->assertEquals("yes##", trim(TemplateEngine::processTemplate('te-core-static_init.tpl', false)), 'static_init = yes#init = #');
		$this->assertEquals('yes', TemplateEngine::get('static_init'), 'static_init is defined');
		$this->assertEquals('', TemplateEngine::get('init'), 'init is empty');
	}

	public function testStaticAndSessionInitEvent() {
		TemplateEngine :: set('static_init', '');
		TemplateEngine :: set('init', '');
		$this->assertEquals('', TemplateEngine::get('static_init'), 'static_init is empty');
		$this->assertEquals('', TemplateEngine::get('init'), 'init is empty');
		TemplateEngine :: on('static_init', function() {
			TemplateEngine :: set('static_init', 'yes');
		});
		TemplateEngine :: on('init', function() {
			TemplateEngine :: set('init', 'yes');
		});
		$this->assertEquals("yes#yes#", trim(TemplateEngine::processTemplate('te-core-init.tpl', true)), 'static_init = yes#init = yes#');
		$this->assertEquals('yes', TemplateEngine::get('static_init'), 'static_init is defined');
		$this->assertEquals('yes', TemplateEngine::get('init'), 'init is defined');
	}

	public function testOutput() {
		ob_start();
		TemplateEngine :: set('output_test', 'huge success');
		TemplateEngine::output('te-core-output.tpl', false);
		$result = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('huge success', $result, 'output is a huge success');
	}

	public function testOutputGzip() {
		ob_start();
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
		TemplateEngine :: set('output_test', 'huge success');
		TemplateEngine::output('te-core-output.tpl', false);
		$result = ob_get_contents();
		ob_end_clean();
		$this->assertEquals(gzencode('huge success'), $result, 'output is a huge gzipped success');
	}

	public function testMessagingConvenienceFunctions() {
		/* RM */require_once('plugins/TE_FOREACH_INLINE.php');/* /RM */
		TemplateEngine :: Error('some error');
		TemplateEngine :: Warning('some warning');
		TemplateEngine :: Info('some info');
		$this->assertEquals("error: some error;warning: some warning;info: some info;", trim(TemplateEngine::processTemplate('te-core-error-warning-info.tpl', true)), 'errors, warnings and infos');
	}

	public function testNonStaticCallRerouting() {
		TemplateEngine :: set('rerouted_set', '');
		$this->assertEquals('', TemplateEngine::get('rerouted_set'), 'rerouted_set is empty');
		$te = TemplateEngine :: Inst();
		$te->set('rerouted_set', 'succeeded');
		$this->assertEquals('succeeded', TemplateEngine :: get('rerouted_set'), 'set call on TemplateEngine object redirected to the static set class function');
	}

	public function testWarningWhileRunning() {
		$this->assertEquals(0, count(TemplateEngine::get('TE_WARNINGS')), 'no warnings in array');
		TemplateEngine :: Warning("some warning");
		$this->assertEquals(1, count(TemplateEngine::get('TE_WARNINGS')), '1 warning in array (self test)');
		TemplateEngine :: on('static_init', function() {
			TemplateEngine :: Warning("some other warning");
		});
		TemplateEngine::processTemplate('te-core-test_warning_while_running.tpl', true);
		$this->assertEquals(1, count(TemplateEngine::get('TE_WARNINGS')), 'no additional warnings in array');
	}

	/**
	 * @expectedException Exception
	 */
	public function testNonexistentFunctionThrows() {
		$te = new TemplateEngine();
		$te->someNonexistentFunction('this-will-fail.sohard');
	}

	public function testDisablingSecuritySettingsShowsAWarning() {
		$logs = array();
		TemplateEngine :: on('log', function($msg, $success, $mode) use (&$logs) {
			$logs[] = array($msg, $success, $mode);
		});
		TemplateEngine :: option('force_tpl_extension', false);
		TemplateEngine :: option('jail_to_template_path', false);
		$this->assertEquals(1, count($logs));
	}

	public function testDebugFiles() {
		TemplateEngine :: option('debug_files', true);
		$expected = "<!-- start templates/te-core-debug-files.tpl -->\nFile content\n<!-- end templates/te-core-debug-files.tpl -->";
		$actual = trim(TemplateEngine :: processTemplate('te-core-debug-files.tpl', false));
		$this->assertEquals($expected, $actual);
	}

	public function testPrintTimingStatistics() {
		ob_start();
		TemplateEngine :: option('timing', true);
		TemplateEngine :: processTemplate('te-core-output.tpl', false);
		TemplateEngine :: shutdown_function();
		$result = ob_get_contents();
		ob_end_clean();
		$tree = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><result>' . $result . '</result>');
		$rows = array();
		foreach($tree->div->table->tr as $k => $tr) {
			$row = array();
			foreach($tr->td as $td) {
				$row[] = '' . $td;
			}
			$rows[] = $row;
		}
		$this->assertGreaterThan(2, count($rows), 'more than two entries in the timing statistics');
		$this->assertEquals('TEincluded', $rows[0][0], 'first entry is TEincluded');
		$this->assertEquals('0 ms', $rows[0][1], 'first offset is 0 ms');
		$this->assertGreaterThan(0, floatval($rows[0][2]), 'parse time of the templateengine scripts should be low');
		// 50ms is not seriously my acceptance level, but the test should be stable even on my slow netbook when doing coverage where php is executed a lot slower than usually
		$this->assertLessThan(50, floatval($rows[0][2]), 'parse time of the templateengine scripts should be low');
		$this->assertGreaterThan(0, floatval($rows[0][3]), 'memory should be greater than 0');
		$this->assertGreaterThan(0, floatval($rows[0][4]), 'peak memory should be greater than 0');

		$this->assertEquals('printTimingStatistics', $rows[count($rows) - 1][0], 'last entry is printTimingStatistics');
		$this->assertGreaterThan(0, floatval($rows[count($rows) - 1][2]), 'execution time should be greater than 0 ms');
		$this->assertGreaterThan(0, floatval($rows[count($rows) - 1][3]), 'memory should be greater than 0');
		$this->assertGreaterThan(0, floatval($rows[count($rows) - 1][4]), 'peak memory should be greater than 0');
	}

	public function testPrintPluginProfiling() {
		ob_start();
		TemplateEngine :: option('plugin_profiling', true);
		TemplateEngine :: processTemplate('te-core-output.tpl', false);
		TemplateEngine :: shutdown_function();
		$result = ob_get_contents();
		ob_end_clean();
		$tree = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><result>' . $result . '</result>');
		$rows = array();
		foreach($tree->xpath('//table/tr') as $tr) {
			$row = array();
			foreach($tr->xpath('td') as $td) {
				$row[] = '' . $td;
			}
			$rows[] = $row;
		}
		$this->assertGreaterThan(1, count($rows), 'more than one entry in the profiling output');
		$sums = array(0, 0, 0, 0);
		foreach($rows as $idx => $r) {
			if ($idx < (count($rows) - 1)) {
				$sums[0] += floatval($r[1]);
				$sums[1] += intval($r[2]);
				$sums[2] += intval($r[3]);
				$sums[3] += intval($r[4]);
			}
		}
		$this->assertGreaterThan(0, $sums[0], 'execution time should be greater than 0 ms');
		$this->assertGreaterThan(0, $sums[1], 'try should be greater than 0');
		$this->assertGreaterThan(0, $sums[2], 'hit should be greater than 0');
		$this->assertGreaterThan(0, $sums[3], 'decline should be greater than 0');
		$this->assertEquals('Total', $rows[count($rows) - 1][0], 'last entry is Total');
		$this->assertEquals($sums[0], floatval($rows[count($rows) - 1][1]), 'execution time should be the sum of all plugins');
		$this->assertEquals($sums[1], intval($rows[count($rows) - 1][2]), 'try should be the sum of all plugins');
		$this->assertEquals($sums[2], intval($rows[count($rows) - 1][3]), 'hit should be the sum of all plugins');
		$this->assertEquals($sums[3], intval($rows[count($rows) - 1][4]), 'decline should the sum of all plugins');
	}

	public function testDisablingBuiltInErrorHandlerDisablesGzipping() {
		$this->assertEquals(true, TemplateEngine :: option('gzip'), 'gzip should be enabled by default');
		TemplateEngine :: useTEErrorHandler(false);
		$this->assertEquals(false, TemplateEngine :: option('gzip'), 'gzip should be disabled if the built in error handler is not used (because php could print errors itself and gzipping the response would break it)');
	}

	public function testDumpVariables() {
		ob_start();
		TemplateEngine :: option('dump_variables', true);
		TemplateEngine :: set('MY_TEST_VAR', 'MY_TEST_VALUE');
		TemplateEngine :: processTemplate('te-core-output.tpl', false);
		TemplateEngine :: shutdown_function();
		$result = ob_get_contents();
		ob_end_clean();
		$this->assertGreaterThan(-1, strstr($result, 'MY_TEST_VAR'), 'MY_TEST_VAR variable should be dumped');
		$this->assertGreaterThan(-1, strstr($result, 'MY_TEST_VALUE'), 'MY_TEST_VALUE variable should be dumped');
		$this->assertGreaterThan(-1, strstr($result, 'te-core-output.tpl'), 'the name of the processed template should be dumped');
	}
}

//EOF
