<?php
/**
 * @copyright Copyright (c) 2010, Obexer Christoph
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * Created on 20.11.2008
 * @author Obexer Christoph
 * @version v2.0
 * @package TemplateEngine
 */
require_once(dirname(realpath(__FILE__)).'/TE_setup2.php');

/**
 * class TEMode specifies the mode for the messages the TemplateEngine will display
 */
final class TEMode {
	const debug =  0;
	const warning =   5;
	const error = 10;
	const none =  15;
}
/**
 * class TemplateEngine
 * this is the class that implements the core TemplateEngine's framework
 */
class TemplateEngine {
	/**
	 * @static TEMode log level of the TemplateEngine
	 */
	private static $mode = TEMode :: error;
	/**
	 * @static boolean forces the log level to the forced one(Templates can not override it)
	 */
	private static $mode_forced = false;
	/**
	 * @static boolean put filename into a comment for all loaded files
	 */
	private static $debug_files = false;
	/**
	 * @static boolean enable forcing the file extension to tpl (disallow including .php,...)
	 */
	private static $force_tpl_extension = true;
	/**
	 * @static boolean disallow any file access outside the template path
	 */
	private static $jail_to_template_path = true;
	/**
	 * @static boolean set to true at the beginning of the first context processing
	 */
	private static $running = false;
	/**
	 * @static boolean inidates if an active session is available and the session-data
	 * init-function should be called
	 */
	private static $havingSession = false;
	/**
	 * @static boolean used to programmatically disable gzipping
	 */
	private static $allow_gzip = true;
	/**
	 * @static array array containing the messages to be displayed
	 */
	private static $messages = array();
	/**
	 * @static array array containing unfinished messages
	 */
	private static $messages_NotFin = array();
	/**
	 * @static integer last elapsed millisecond time
	 */
	private static $timing_last = 0;
	/**
	 * @static array containing timing information
	 */
	private static $timing_data = array();
	/**
	 * @static boolean enable time capturing
	 */
	private static $timing_enabled = false;
	/**
	 * @static TemplateEngine instance of the TemplateEngine
	 */
	private static $instance = null;
	/**
	 * @static array containing all contexts
	 */
	private static $contexts = array();
	/**
	 * @static array containing all set template variables
	 */
	private static $variables = array();
	/**
	 * @static array containing all known escape/formatting methods
	 */
	private static $escapeMethod = array();
	/**
	 * @static array containing all escape/formatter configuration
	 */
	private static $escapeMethodConfig = array();
	/**
	 * @static array array containing all registered plugins
	 */
	private static $pluginRegistration = array();
	/**
	 * @static string name of the current active plugin
	 */
	private static $activePlugin = "";
	/**
	 * @static string the root path of the application as seen by the browser,
	 * available to the templates as {ROOT_PATH}
	 */
	private static $rootPath = '';
	/**
	 * @static string the template path of the application relative to the root,
	 * available to the templates as {TEMPLATE_PATH}
	 */
	private static $templatePath = '';
	/**
	 * @static array cache for files accessed during template processing
	 */
	private static $templateCache = array();
	/**
	 * @static this variable is used to track the name of the basetemplate
	 */
	private static $basetemplate = '';
	/**
	 * __construct
	 * initializes a new object of the TemplateEngine
	 * @access public
	 */
	public function __construct() {
		if(null === self :: $instance) {
			self :: $instance = $this;
			self :: clear();
		}
	}
	/**
	 * Inst
	 * returns a TemplateEngine instance
	 * @return TemplateEngine
	 * @access public
	 * @static
	 */
	public static function Inst() {
		if(self :: $instance === null) {
			self :: $instance = new self();
		}
		return self :: $instance;
	}
	/**
	 * clear
	 * clears all template variables and messages
	 * @return void
	 * @access public
	 * @static
	 */
	public static function clear() {
		self :: $variables = array();
		self :: $variables['TE_ERRORS'] = array();
		self :: $variables['TE_WARNINGS'] = array();
		self :: $variables['TE_INFOS'] = array();
		self :: $variables['HEADER_TEXT'] = '';
		self :: $messages_NotFin = array();
		self :: $messages = array();
		self :: $contexts = array();
	}
	/**
	 * pushConetxt
	 * push a new template context onto the context stack
	 * @param $templateString string the template string to process in this context
	 * @param $context array the context to work on
	 * @return void
	 */
	public static function pushContext($templateString, array $context) {
		array_push(self :: $contexts, array(
			'tpl' => $templateString,
			'ctx' => $context,
			'hit' => 0,
			'miss' => 0,
			'prevContextActivePlugin' => self :: $activePlugin
		));
		self :: processCurrentContext();
		return self :: endContext();
	}
	/**
	 * endContext
	 * @return current template string
	 */
	public static function endContext() {
		$ctx = array_pop(self :: $contexts);
		self :: $activePlugin = $ctx['prevContextActivePlugin'];
		return $ctx['tpl'];
	}
	/**
	 * registerEscapeMethod
	 * register additional escape/formatter
	 * @param $method string name of the escape/formatter method
	 * @param $callback callback function to be called when the formatter is used
	 * @return void
	 * @todo document callback interface
	 */
	public static function registerEscapeMethod($method, $callback) {
		self :: $escapeMethod[$method] = $callback;
	}
	/**
	 * unregisterEscapeMethod
	 * delete escape/formatter method and configuration
	 * @param $method string name of the escape/formatter method
	 * @return void
	 */
	public static function unregisterEscapeMethod($method) {
		unset(self :: $escapeMethod[$method]);
		unset(self :: $escapeMethodConfig[$method]);
	}
	/**
	 * setEscapeMethodConfig
	 * @param $method string name of the escape/formatter method
	 * @param $config mixed method configuration
	 * @return void
	 */
	public static function setEscapeMethodConfig($method, $config) {
		self :: $escapeMethodConfig[$method] = $config;
	}
	/**
	 * getEscapeMethodConfig
	 * @param $method string name of the escape/formatter method
	 * @return $config mixed method configuration or null
	 */
	public static function getEscapeMethodConfig($method) {
		return isset(self :: $escapeMethodConfig[$method]) ? self :: $escapeMethodConfig[$method] : null;
	}
	/**
	 * escape
	 * pass the value to the escape method and escape it if possible
	 * @param string $escaper the escaper method to call
	 * @param mixed $value the value to be escaped
	 * @return mixed result of the escape method call
	 */
	public static function escape($escaper, $value) {
		if (!isset(self :: $escapeMethod[$escaper])) {
			self :: LogMsg('Escape method <em>' . $escaper . '</em> unknown!');
			return $value;
		}
		return call_user_func_array(self :: $escapeMethod[$escaper], array($value, self :: getEscapeMethodConfig($escaper)));
	}
	/**
	 * registerPlugin
	 * register the given plugin for mathes of the given regular expression
	 * @param $plugin string the name of the plugin to register(must be unique)
	 * @param $regexp string the regular expression whose matches will be handled by the callback
	 * @param $callback callback the function to call with any found match
	 * @return void
	 * @todo document callback interface
	 */
	public static function registerPlugin($plugin, $regexp, $callback) {
		self ::$pluginRegistration[$plugin] = array(
			'regex' => $regexp,
			'cb' => $callback
		);
	}
	/**
	 * unregisterPlugin
	 * unregister the plugin with the given name
	 * @param $plugin string the name of the plugin to unregister
	 * @return void
	 */
	public static function unregisterPlugin($plugin) {
		unset(self ::$pluginRegistration[$plugin]);
	}
	/**
	 * setTemplatePath
	 * tell the TemplateEngine where to search for the template files
	 * the template path is available to templates as: {TEMPLATE_PATH}
	 * <strong>Note: this path will always end with a /</strong>
	 * @param $path string the path of the template files
	 * @return void
	 */
	public static function setTemplatePath($path) {
		$len = strlen($path);
		if($len && !('/' == $path[$len - 1])) {
			$path .= '/';
		}
		self :: $templatePath = $path;
		//set the template Path so templates can use it
		self :: set('TEMPLATE_PATH', self :: $rootPath . self :: $templatePath);
	}
	/**
	 * getTemplatePath
	 * returns the template path currently set
	 * @return string the path of the template files
	 */
	public static function getTemplatePath() {
		return self :: $templatePath;
	}
	/**
	 * setRootPath
	 * sets the root path of the gombg directory seen from the browser!
	 * the root path is available to the templates as {ROOT_PATH}
	 * <strong>Note: this path will always end with a /</strong>
	 * @param $path string the root path of the application as seen by the browser
	 * @return void
	 */
	public static function setRootPath($path) {
		$len = strlen($path);
		if($len && !('/' == $path[$len - 1])) {
			$path .= '/';
		}
		elseif (0 === $len) {
			$path = '/';
		}
		self :: $rootPath = $path;
		//set the root Path so templates can use it
		self :: set('ROOT_PATH', self :: $rootPath);
		self :: set('TEMPLATE_PATH', self :: $rootPath . self :: $templatePath);
	}
	/**
	 * getRootPath
	 * returns the currently set root path
	 * <strong>Note: this path will always end with a /</strong>
	 * @return string the root path of the application as seen by the browser
	 */
	public static function getRootPath() {
		return self :: $rootPath;
	}
	/**
	 * processCurrentContext
	 * process the context on top of the stack and update its 'tpl' member
	 * @return void
	 */
	private static function processCurrentContext() {
		//TODO: push context recursion
		$recursion_limit = 32;
		$ctx = &self :: $contexts[count(self :: $contexts) - 1];
		if(strlen($ctx['tpl']) > 0) {
			do {
				$ctx['hit'] = 0;
				$ctx['miss'] = 0;
				foreach (self :: $pluginRegistration as $plugin => $pdata) {
					self :: $activePlugin = $plugin;
					$ctx['tpl'] = preg_replace_callback($pdata['regex'], array('TemplateEngine', 'replace_callback'), $ctx['tpl']);
				}
			}
			while(($ctx['hit'] > 0) && $recursion_limit--);
		}
	}
	/**
	 * output
	 * process the given basetemplate and output results to the browser
	 * @param string $basetemplate filename relative to TEMPLATE_PATH
	 * @param boolean $havingSession indicates wheter this runs in a user context or cron context (default: true)
	 * @return void
	 */
	public static function output($basetemplate, $havingSession = true) {
		//TODO: allow other content-types (application/json, application/javascript, text/css,...)
		header("Content-Type: text/html; charset=utf-8");
		$result = self :: processTemplate($basetemplate, $havingSession);
		//compress using gzip if the browser supports it
		if (self :: $allow_gzip && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
			//well if YOU used print/echo before the complete content will be garbage it's YOUR fault!
			header('Content-Encoding: gzip');
			//TODO: ob_start, ob_gz_handler?
			print gzencode($result);
		}
		else {
			print $result;
		}
	}
	/**
	 * processTemplate
	 * process the given basetemplate and output results to the browser
	 * @param string $basetemplate filename relative to TEMPLATE_PATH
	 * @param boolean $havingSession indicates wheter this runs in a user context or cron context (default: true)
	 * @return string the processed template string
	 */
	public static function processTemplate($basetemplate, $havingSession = true) {
		self :: $havingSession = $havingSession;
		self :: $basetemplate = $basetemplate;
		self :: captureTime('startTE'); //< init time measurement
		self :: $running = true; //< if a Info/Warning/Error function is called while running it can not be guaranteed to work correctly -> print it!
		TE_static_setup(); //< common static (session independent) data will be set here
		if(self :: $havingSession) {
			TE_setup(); //< do TE initialisation (common data will be set there)
		}
		$tpl = '';
		if (!self :: getFile($basetemplate, $tpl)) {
			die('TemplateEngine Error: basetemplate not found - or not readable! ' . $basetemplate);
		}
		$result = self :: pushContext($tpl, self :: $variables);
		self :: captureTime('stopTE');
		if (self :: $debug_files) {
			$basetemplate = str_replace(realpath(self :: $rootPath) . '/', '', realpath(self :: $rootPath . self :: $templatePath . $basetemplate));
			$result = "<!-- basetemplate: $basetemplate -->\n" . $result;
		}
		$result = str_replace('</body>', (self :: formatLogMessages() . '</body>'), $result);
		return $result;
	}
	/**
	 * replace_cllback
	 * this callback is called from preg_replace_callback and calls the current plugin
	 * @param array $match match found by preg_replace_callback
	 * @return string the replacement for the match
	 */
	public static function replace_callback(array $match) {
		$callback = self :: $pluginRegistration[self :: $activePlugin]['cb'];
		$ctx = &self :: $contexts[count(self :: $contexts) - 1];
		$res = call_user_func_array($callback, array($ctx['ctx'], $match));
		if (false !== $res) {
			++$ctx['hit'];
			return $res;
		}
		++$ctx['miss'];
		return $match[0];
	}
	/**
	 * set
	 * set the value with the given name to the given value
	 * @param string $name name of the value
	 * @param mixed $value the value
	 * @return void
	 */
	public static function set($name, $value) {
		self :: $variables[$name] = $value;
	}
	/**
	 * delete
	 * delete the variable with the given name
	 * @param string $name name of the variable
	 * @return void
	 */
	public static function delete($name) {
		unset(self :: $variables[$name]);
	}
	/**
	 * get
	 * get the value with the given name or the given default value(or null)
	 * @param string $name name of the value
	 * @param mixed $default the default value if the value was not set before
	 * @return void
	 */
	public static function get($name, $default = null) {
		if (isset(self :: $variables[$name])) {
			return self :: $variables[$name];
		}
		return $default;
	}
	/**
	 * lookupVar
	 * look up the value of the given variable in the context stack in the correct order (now -> global)
	 * will return true if the value has been found and will only change the second param if found
	 * @param string $name name of the variable
	 * @param mixed $value passed by reference, modified if the value has been found - gets the found value
	 * @return boolean true if the value has been found, false either
	 */
	public static function lookupVar($name, &$value) {
		self :: LogMsg('Lookup: <em>' . $name . '</em>', true, TEMode :: debug, false);
		$found = false;
		$idx = count(self :: $contexts);
		do {
			--$idx;
			if(isset(self :: $contexts[$idx]['ctx'][$name])) {
				$value = self :: $contexts[$idx]['ctx'][$name];
				$found = true;
			}
		}
		while(!$found && $idx > 0);
		self :: LogMsg($found ? '':' failed', $found, TEMode :: debug);
		return $found;
	}
	/**
	 * Error
	 * add an error message to display to the user
	 * @param string $error message
	 */
	public static function Error($error) {
		if (!self :: $running) {
			array_push(self :: $variables['TE_ERRORS'], array('CLASS' => 'error', 'TEXT' => $error));
		}
		else {
			self :: LogMsg('[ERROR]: ' . $error, false, TEMode :: error);
		}
	}
	/**
	 * Warning
	 * add a warning message to display to the user
	 * @param string $warning message
	 */
	public static function Warning($warning) {
		if (!self :: $running) {
			array_push(self :: $variables['TE_WARNINGS'], array('CLASS' => 'warning', 'TEXT' => $warning));
		}
		else {
			self :: LogMsg('[WARNING]: ' . $warning, false, TEMode :: error);
		}
	}
	/**
	 * Info
	 * add a info message to display to the user
	 * @param string $info message
	 */
	public static function Info($info) {
		if (!self :: $running) {
			array_push(self :: $variables['TE_INFOS'], array('CLASS' => 'info', 'TEXT' => $info));
		}
		else {
			self :: LogMsg('[INFO]: ' . $info, false, TEMode :: error);
		}
	}
	/**
	 * setTitle
	 * set the page title accessible for templates using
	 * @param string $title
	 * @return void
	 */
	public static function setTitle($title) {
		return self :: set('PAGE_TITLE', $title);
	}
	/**
	 * header
	 * adds the given string to the HTML head section of the current page
	 * (to {HEADER_TEXT} which is in _header.tpl)
	 * @param $html html to be added to the HTML head section
	 * @return void
	 */
	function header($html) {
		self :: $variables['HEADER_TEXT'] .= "\t" . $html . "\n";
	}
	/**
	 * addCSS
	 * add a reference to a css file to the current page
	 * <strong>Provide the FULL path!(browser relative of course)</strong>
	 * @param string $css filename to be set as the href attribute of the link tag
	 * @return void
	 */
	public static function addCSS($css) {
		$t = "\t".'<link type="text/css" rel="stylesheet" href="' . $css . '" />'."\n";
		self :: $variables['HEADER_TEXT'] .= $t;
	}
	/**
	 * addJS
	 * add a reference to a js file to the current page
	 * <strong>Provide the FULL path!(browser relative of course)</strong>
	 * @param string $js filename to be set as the src attribute of the script tag
	 * @return void
	 */
	function addJS($js) {
		$t = "\t".'<script type="text/javascript" src="' . $js . '" ></script>'."\n";
		self :: $variables['HEADER_TEXT'] .= $t;
	}
	/**
	 * setArray
	 * compatibility function
	 * @deprecated use set directly
	 */
	//FIXME: done for compatibility
	public static function setArray($name, $value) {
		self :: LogMsg("setArray for <em>$name</em> deprecated!", false, TEMode :: warning, true);
		return self :: set($name, $value);
	}
	/**
	 * LogMsg
	 * this function adds a log message to the Message Log
	 * @param string $msg log message
	 * @param boolean $success states if the event was a success or not
	 * @param integer $mode type of this message ({@See TEMode}).
	 * @param boolean $finished if this value is true the message will be enqueued into the output buffers,
	 * otherwise it will be pushed onto a stack and popped when the next finished message gets added, this
	 * allows for multiple non-finished messages at the same time
	 * @return void
	 */
	public static function LogMsg($msg, $success = true, $mode = TEMode :: debug, $finished= true) {
		if(!$finished) {
			array_push(self :: $messages_NotFin, $msg);
			return;
		}
		else {
			if(!empty(self :: $messages_NotFin)) {
				$oldmsg = implode('', self :: $messages_NotFin);
				self :: $messages_NotFin = array();
				$msg = $oldmsg . $msg;
			}
			$item = array();
			$item['mode'] = $mode;
			$item['msg'] = $msg;
			$item['success'] = $success;
			self :: $messages[] = $item;
			if (function_exists('flog')) {
				//TODO: choose function based on $mode
				flog(($mode !== TEMode :: error) ? 'info' : 'error', $msg);
			}
			if (class_exists('AJAX', false)) {
				//TODO: choose function based on $mode
				AJAX::warning($msg);
			}
		}
	}
	/**
	 * formatLogMessages
	 * format messages logged with LogMsg
	 * @return string generated html
	 */
	private static function formatLogMessages() {
		self :: LogMsg('<em>formatting messages...</em>', true, TEMode :: debug);
		$succ = array(
			true => '[ <strong class="te_msg_done">done</strong> ]',
			false => '[<strong class="te_msg_failed">failed</strong>]'
		);
		$mode = array(
			TEMode :: debug   => '<strong class="te_msg_dbg">[ DEBUG ]: </strong>',
			TEMode :: warning => '<strong class="te_msg_wrn">[WARNING]: </strong>',
			TEMode :: error   => '<strong class="te_msg_err">[ ERROR ]: </strong>',
			TEMode :: none    => '<strong class="te_msg_non">[  NONE ]: </strong>'
		);
		if (count(self :: $messages)) {
			$html = '<div id="te_message_log">';
			foreach(self :: $messages as $message) {
				if ($message['mode'] >= self :: $mode) {
					$html .= '<div>';
					$html .= @$mode[$message['mode']];
					$html .= '<span>' . $succ[$message['success']] . '</span>';
					$html .= $message['msg'];
					$html .= '</div>';
				}
			}
			return $html . '</div>';
		}
		return '';
	}

	/**
	 * captureTime
	 * captures the number of milliseconds elapsed since the first call to this function(done on TE include)
	 * @param $milestone string name of the milestone
	 * @return void
	 */
	public static function captureTime($milestone) {
		$item = array();
		$item['milestone'] = $milestone;
		$item['time'] = microtime(true) * 1000;
		$item['mem'] = round(memory_get_usage(true) / 1024, 0);
		$item['peakmem'] = round(memory_get_peak_usage(true) / 1024, 0);
		self :: $timing_data[] = $item;
	}
	/**
	 * printTimingStatistics
	 * prints a table listing all milestones, their relative time and the time between two milestones
	 * <strong>Note: this function is called automatically on script shutdown! (if enabled)</strong>
	 * @return void
	 */
	public static function printTimingStatistics() {
		self :: captureTime('printTimingStatistics');
		$last = 0;
		$first = 0;
		foreach(self :: $timing_data as $idx => $mdata) {
			$last = $mdata['time'];
			break;
		}
		$first = $last;
		print '<hr /><div style="background-color:#888;"><table style="margin: 0 auto;';
		print 'border:1px solid green;width:70%;"><thead><tr><th>Milestone Name</th>';
		print '<th>Start Offset</th><th>Exec-Time</th><th>Memory</th><th>Peak Memory</th></tr></thead>';
		foreach(self :: $timing_data as $idx => $mdata) {
			print '<tr><td>'.$mdata['milestone'].'</td><td style="text-align: right;">';
			print round(($mdata['time'] - $first), 2).' ms</td><td style="text-align: right;">';
			print round(($mdata['time'] - $last), 2).' ms</td><td style="text-align: right;">';
			print $mdata['mem'].' KiB</td><td style="text-align: right;">'.$mdata['peakmem'].' KiB</td></tr>';
			$last= $mdata['time'];
		}
		print '</table></div>';
	}

	/**
	 * forceMode
	 * force the given log level, ignore Templates setting the level differently
	 * @return void
	 */
	public static function forceMode($mode) {
		self :: $mode = $mode;
		self :: $mode_forced = true;
	}

	/**
	 * setFileDebugMode
	 * enable or disable file debugging mode
	 * @param boolean $mode set to true to enable comment insertion
	 */
	public static function setFileDebugMode($mode) {
		self :: $debug_files = $mode;
	}

	/**
	 * setForceTplExtension
	 * enable or disable file extension sanity check
	 * @param boolean $mode set to true to enable sanity check
	 */
	public static function setForceTplExtension($mode) {
		self :: $force_tpl_extension = $mode;
		if (!self :: $jail_to_template_path && !self :: $force_tpl_extension) {
			self :: LogMsg('Security settings disabled, use with extreme caution!', false, TEMode :: error);
		}
	}

	/**
	 * setJailToTemplatePath
	 * enable or disable file accessing files outside the set template path
	 * @param boolean $mode set to true to enable security check
	 */
	public static function setJailToTemplatePath($mode) {
		self :: $jail_to_template_path = $mode;
		if (!self :: $jail_to_template_path && !self :: $force_tpl_extension) {
			self :: LogMsg('Security settings disabled, use with extreme caution!', false, TEMode :: error);
		}
	}

	/**
	 * noGzip
	 * disallow gzipping
	 * @return void
	 */
	public static function noGzip() {
		self :: $allow_gzip = false;
	}

	/**
	 * enableTiming
	 * set capturing of timing information to on
	 * @return void
	 */
	public static function enableTiming() {
		self :: noGzip();
		self :: $timing_enabled = true;
		register_shutdown_function(array('TemplateEngine', 'printTimingStatistics'));
	}

	/**
	 * useTEErrorHandler
	 * internal function to enable the internal error handler don't call!
	 * @param boolean $use
	 * @return void
	 */
	public static function useTEErrorHandler($use) {
		if($use) {
			set_error_handler("TE_php_err_handler");
		}
		else {
			self :: noGzip();
		}
	}

	/**
	 * reroute non static calls to the static functions
	 * @param string $method
	 * @param array $args
	 */
	public function __call($method, $args) {
		if (function_exists(array('TemplateEngine', $method))) {
			return call_user_func_array(array('TemplateEngine', $method), $args);
		}
		die("The method 'TemplateEngine::$method' does not exist!");
	}

	/**
	 * getFile
	 * read the file (log that) and get the content
	 * @param string $name filename(relative to TEMPLATE_PATH)
	 * @param string $content passed by reference contains the file content on success, unmodified otherwise
	 * @return boolean true if the file was found, readable and loaded
	 */
	public function getFile($name, &$content) {
		$fname = realpath(self :: $rootPath . self :: $templatePath . $name);
		$content = '';
		self :: LogMsg('[getFile]: <em>"' . $name . '"</em> ', true, TEMode :: debug, false);
		if (!isset(self :: $templateCache[$fname])) {
			if (!file_exists($fname) || !is_readable($fname)) {
				self :: LogMsg('file not found!', false, TEMode :: error, true);
				return false;
			}
			if (self :: $force_tpl_extension && !preg_match('/\.tpl$/', $fname)) {
				self :: LogMsg('invalid file', false, TEMode :: error);
				return false;
			}
			if (self :: $jail_to_template_path) {
				$tplpath = realpath(self :: $rootPath . self :: $templatePath);
				if (0 !== strncmp($tplpath, $fname, strlen($tplpath))) {
					self :: LogMsg('access denied', false, TEMode :: error);
					return false;
				}
			}
			self :: LogMsg(' Cache MISS', true, TEMode :: debug, true);
			$content = file_get_contents($fname);
			self :: $templateCache[$fname] = $content;
		}
		else {
			self :: LogMsg(' Cache HIT', true, TEMode :: debug, true);
			$content = self :: $templateCache[$fname];
		}
		if (self :: $debug_files) {
			$fname = str_replace(realpath(self :: $rootPath) . '/', '', $fname);
			$content = "<!-- start $fname -->\n" . $content . "<!-- end $fname -->\n";
		}
		return true;
	}

	public static function dumpVariablesOnExit() {
		register_shutdown_function(array('TemplateEngine', 'dumpVariables'));
		self :: noGzip();
	}

	public static function dumpVariables() {
		$template = self :: $basetemplate;
		print '<pre style="text-align:left;background-color:white;">';
		print "Basetemplate: $template\n";
		print "Available Template-Variables:\n";
		print htmlentities(print_r(self :: $variables, true));
		print "</pre>";
	}
// builtin template directives

	private static function TE_SKALAR(array $ctx, array $match) {
		$val = null;
		$found = false;
		if (isset( $ctx[$match[1]])) {
			$val = $ctx[$match[1]];
			$found = true;
		}
		elseif(self :: lookupVar($match[1], $val)) {
			$found = true;
		}
		if($found && isset($match['escaper']) && '' != $match['escaper']) {
			return self :: escape($match['escaper'], $val);
		}
		elseif($found) {
			return (string)$val;
		}
		return false;
	}

	private static function TE_LOAD(array $ctx, array $match) {
		$content = '';
		self :: LogMsg('[LOAD]', true, TEMode :: debug, false);
		$succ = self :: getFile($match[1], $content);
		return $succ ? $content : false;
	}

	private static function TE_LOAD_WITHID(array $ctx, array $match) {
		$content = '';
		self :: LogMsg('[LOAD_WITHID]', true, TEMode :: debug, false);
		$succ = self :: getFile($match[1], $content);
		return $succ ? str_replace("{LOAD:ID}", $match[2], $content) : false;
	}

	private static function TE_FOREACH_FILE(array $ctx, array $match) {
		$val = null;
		$found = false;
		if (isset($ctx[$match[1]])) {
			$val = $ctx[$match[1]];
			$found = true;
		}
		elseif (self :: lookupVar($match[1], $val)) {
		    $found = true;
		}

		if(!$found || !is_array($val)) {
			self :: LogMsg('[FOREACH_FILE]: Variable <em>'.$match[1].'</em> not set or invalid', false, TEMode::error);
			return false;
		}
		$fname = $match[2];
		if(empty($val)) {
			$fname = str_replace('.tpl', '-empty.tpl', $fname);
			$val[] = array(); //append empty element to make the rest work
		}
		$tpl = '';
		self :: LogMsg('[FOREACH_FILE]', true, TEMode :: debug, false);
		$succ = self :: getFile($fname, $tpl);
		if (!$succ) {
			return false;
		}
		$res = '';
		$iteration = 0;
		foreach($val as $index => $lctx) {
			$lctx['ODDROW'] = (($iteration % 2) == 0) ? 'odd' : '';
			$ctpl = str_replace('{FOREACH:INDEX}', $index, $tpl);
			$res .= self :: pushContext($ctpl, $lctx);
			$iteration++;
		}
		return $res;
	}

	private static function TE_FOREACH_INLINE(array $ctx, array $match) {
		$val = null;
		$found = false;
		if (isset($ctx[$match['variable']])) {
			$val = $ctx[$match['variable']];
			$found = true;
		}
		elseif (self :: lookupVar($match['variable'], $val)) {
			$found = true;
		}

		if(!$found || !is_array($val)) {
			self :: LogMsg('[FOREACH_INLINE]: Variable <em>'.$match['variable'].'</em> not set or invalid', false, TEMode::error);
			return false;
		}
		$block = $match['block'];
		if(empty($val)) {
			$block = $match['nblock'];
			$val[] = array();
		}
		$res = '';
		$iteration = 0;
		foreach($val as $index => $lctx) {
			$lctx['ODDROW'] = (($iteration % 2) == 0) ? 'odd' : '';
			$ctpl = str_replace('{FOREACH:INDEX}', $index, $tpl);
			$res .= self :: pushContext($block, $lctx);
			$iteration++;
		}
		return $res;
	}

	private static function TE_SELECT(array $ctx, array $match) {
		$html = '';
		$val = null;
		if(isset($ctx[$match[1]])) {
			$val = $ctx[$match[1]];
		}
		else {
			self :: lookupVar($match[1], $val);
		}
		if(!is_array($val)) {
			self :: LogMsg('[SELECT]: Array <em>"'.$match[1].'"</em> not set or invalid', false, TEMode :: error);
		}
		else {
			self :: LogMsg('[SELECT]: rendering Array <em>"'.$match[1].'"</em>', true, TEMode :: debug);
			foreach($val as $values) {
				$html .= '	<option name="'.$values['NAME'].'" value="'.$values['VALUE'].'">'.$values['NAME'].'</option>';
			}
		}
		return $html;
	}


	private static function TE_LOGLEVEL(array $ctx, array $match) {
		if (self :: $mode_forced) {
			return '';
		}
		switch($match[1]) {
			case 'DEBUG':  self :: $mode = TEMode :: debug;  return '';
			case 'WARNING':self :: $mode = TEMode :: warning;return '';
			case 'ERROR':  self :: $mode = TEMode :: error;  return '';
			case 'NONE':   self :: $mode = TEMode :: none;   return '';
			default: return false;
		}
	}

	private static function TE_IF(array $ctx, array $match) {
		if(count($match) < 9) {
			self :: LogMsg('[IF]: Directive malformed: <em>'.$match[0].'</em>', false, TEMode::error);
			return false;
		}
		$key = $match['variable'];
		$escaper = $match['escaper'];
		$op = $match['operator'];
		$literal = null;
		if(isset($match['literal']) && '' !== $match['literal']) {
			$literal = $match['literal'];
		}
		elseif(!self :: lookupVar($match['litvar'], $literal)) {
			self :: LogMsg('[IF]: Value <em>'.$match['litvar'].'</em> not set, but used by IF', false, TEMode::error);
			return false;
		}
		$block = $match['block'];
		$nblock = isset($match['nblock']) ? $match['nblock'] : '';
		$val = isset($ctx[$key]) ? $ctx[$key] : null;
		if(null == $val && 'null' != $literal && !self :: lookupVar($key, $val)) {
			self :: LogMsg('[IF]: Value <em>'.$key.'</em> not set, but used by IF', false, TEMode::error);
			return false;
		}
		$result= false;
		//< maybe not best style but easy and works =)
		if('null' == $literal) {
			$literal = null;
		}
		self :: LogMsg('[IF]: Condition: <em>'.$key.('' !== $escaper ? '|'.$escaper : '').' '.$op.' '.($literal === null ? 'null' : $literal).'</em> ', true, TEMode::debug, false);
		if ('' !== $escaper) {
			$val = self :: escape($escaper, $val);
		}
		switch($op) {
			case '<' :
			case 'lt' :
				$result = $val < $literal;
				break;
			case '>' :
			case 'gt' :
				$result = $val > $literal;
				break;
			case '==' :
			case 'eq' :
				$result = $val == $literal;
				break;
			case '!=' :
			case 'ne' :
				$result = $val != $literal;
				break;
			case '<=' :
			case 'lte' :
				$result = $val <= $literal;
				break;
			case '>=' :
			case 'gte' :
				$result = $val >= $literal;
				break;
			default :
				self :: LogMsg('[IF]: Operator not known: <em>'.$op.'</em>('.$match[0].')', false, TEMode::error);
				return false;
		}
		if($result === true) {
			self :: LogMsg('... matched!', true, TEMode::debug);
			$result = $block;
		}
		else {
			self :: LogMsg('... not matched!', true, TEMode::debug);
			$result = $nblock;
		}
		return $result;
	}

	private static function TE_STRIP_INLINESTYLE(array $context, array $match) {
		return '';
	}

	private static function ESC_LEN($value, $config) {
		if(is_array($value)) {
			return count($value);
		}
		if(is_string($value)) {
			return strlen($value);
		}
		return 0; //everything else is unknown atm
	}
};

// #######################################################################################
// setup the use of the TemplateEngine, handle GET parameters, setup php-error-handler,...
// #######################################################################################
// setup TEmplateEngine environment
new TemplateEngine(); //required! as the first instance clears and initializes the TE
TemplateEngine :: captureTime('TEincluded'); //< page start init
TemplateEngine :: useTEErrorHandler(!isset($_GET['force_def_err_handler']));
// register built in plugins
TemplateEngine :: registerPlugin('TE_LOGLEVEL', '/\{LOGLEVEL=(DEBUG|WARNING|ERROR|NONE)\}/', array('TemplateEngine', 'TE_LOGLEVEL'));
TemplateEngine :: registerPlugin('TE_IF',
	'/\{(IF)\((?P<variable>[A-Z0-9_]+)(?:\|(?P<escaper>[A-Z]+))?\s?(?P<operator><|>|==|!=|<=|>=|lt|gt|eq|ne|lte|gte){1}\s?(?:(?P<literal>[\w-]+)|\{(?P<litvar>[A-Z0-9_]+)\})\)\}(?P<block>(?:(?>[^{]*?)|(?:\{)(?!(IF\(([A-Z0-9_]+)(?:\|([A-Z]+))?\s?(<|>|==|!=|<=|>=|lt|gt|eq|ne|lte|gte){1}\s?([\w-]+)\)\}))|(?R))*)(\{IF:ELSE\}(?P<nblock>(?:(?>[^{]*?)|(?:\{)(?!(IF\(([A-Z0-9_]+)(?:\|([A-Z]+))?\s?(<|>|==|!=|<=|>=|lt|gt|eq|ne|lte|gte){1}\s?([\w-]+)\)\}))|(?R))*))?\{\/IF\}/Us',
	array('TemplateEngine', 'TE_IF'));
TemplateEngine :: registerPlugin('TE_LOAD', '/\{LOAD=([^\{\}]+)\}/', array('TemplateEngine', 'TE_LOAD'));
TemplateEngine :: registerPlugin('TE_LOAD_WITHID', '/\{LOAD_WITHID=([^\{\}]+);([^\{\}]+)\}/', array('TemplateEngine', 'TE_LOAD_WITHID'));
TemplateEngine :: registerPlugin('TE_SELECT', '/\{SELECT=([A-Z0-9_]+)\}/', array('TemplateEngine', 'TE_SELECT'));
TemplateEngine :: registerPlugin('TE_FOREACH_FILE', '/\{FOREACH\[([A-Z0-9_]+)\]=([^\}]+)\}/Um', array('TemplateEngine', 'TE_FOREACH_FILE'));
TemplateEngine :: registerPlugin('TE_FOREACH_INLINE', '/\{FOREACH\[(?P<variable>[A-Z0-9_]+)\]\}(?P<block>(?:(?>[^{]*?)|(?:\{)(?!(FOREACH\[([A-Z0-9_]+)\]\}))|(?R))*)(?:\{FOREACH:ELSE\}(?P<nblock>(?:(?>[^{]*?)|(?:\{)(?!(FOREACH\[([A-Z0-9_]+)\]\}))|(?R))*))?\{\/FOREACH\}/Us', array('TemplateEngine', 'TE_FOREACH_INLINE'));
TemplateEngine :: registerPlugin('TE_SKALAR', '/\{([A-Z0-9_]*)(?:\|(?P<escaper>[A-Z0-9_]+))?\}/', array('TemplateEngine', 'TE_SKALAR'));

TemplateEngine :: registerEscapeMethod('LEN', array('TemplateEngine', 'ESC_LEN'));

// force debug level if 'force_debug' is set in $_GET
if(isset($_GET['force_debug'])) {
	TemplateEngine :: forceMode(TEMode :: debug);
}
// activate timing information if 'show_timing' is set in $_GET
if(isset($_GET['show_timing'])) {
	TemplateEngine :: enableTiming();
}
// activate file debugging if 'debug_files' is set in $_GET
if(isset($_GET['debug_files'])) {
	TemplateEngine :: setFileDebugMode(true);
}
// strip all inline styles if 'no_inline' is set in $_GET
if(isset($_GET['no_inline'])) {
	TemplateEngine :: registerPlugin('TE_STRIP_INLINESTYLE', '/(style="(?:[^"]*)")/', array('TemplateEngine', 'TE_STRIP_INLINESTYLE'));
}
//don't gzip if impossible ;)
if (!function_exists('gzencode')) {
	TemplateEngine :: noGzip();
}
// dump name and value of all set template variables
if(isset($_GET['te_dump'])) {
	TemplateEngine :: dumpVariablesOnExit();
}
/**
 * TE_php_err_handler
 * this function is called by php if any error message is encountered
 * @param integer $errno error number identifiing the error
 * @param string $errstr describing error message
 * @param string $errfile filename of the file that caused the error
 * @param integer $errline line number where the error occured
 * @param array $errorcontext unused data that might be given by php
 */
function TE_php_err_handler($errno, $errstr, $errfile= '', $errline= '', $errcontext= array()) {
	TemplateEngine :: LogMsg('#'.$errno.': '.$errstr.' @'.$errfile.'('.$errline.')', false, TEMode::error);
}
//EOF
