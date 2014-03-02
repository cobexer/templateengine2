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
$te_setup_filename = dirname(realpath(__FILE__)) . '/TE_setup2.php';
if (file_exists($te_setup_filename)) {
	require_once($te_setup_filename);
}

const TE_regex_varname = '[A-Za-z0-9_]+';
const TE_regex_escape_method = '[A-Z_]+';

/**
 * class TEMode specifies the mode for the messages the TemplateEngine will display
 */
final class TEMode {
	const debug =  0;
	const warning = 5;
	const error = 10;
	const none =  15;
}

/**
 * class TEEventPhase specifies the phase that the event handler wants to listen for the event to occur.
 */
final class TEEventPhase {
	const evaluate = 0;
	const execute = 1;
	const inform = 2;
}

/**
 * class TEEventRegistration is used to track the registered event handlers for an event.
 */
class TEEventRegistration {
	private $handlers;
	public function __construct() {
		$this->handlers = array(
			TEEventPhase :: evaluate => array(),
			TEEventPhase :: execute => array(),
			TEEventPhase :: inform => array(),
		);
	}
	public function on($phase, $handler) {
		$this->handlers[$phase][] = $handler;
	}
	public function get($phase) {
		return $this->handlers[$phase];
	}
}

/**
 * class TETemplateNotFoundException thrown if a template has not been found
 */
final class TETemplateNotFoundException extends Exception {
}

/**
 * class TEPluginRegexInvalidException thrown if a plugin registered a broken regex.
 */
final class TEPluginRegexInvalidException extends Exception {
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
	 * @static boolean set to true at the beginning of the first context processing
	 */
	private static $running = false;
	/**
	 * @static boolean inidates if an active session is available and the session-data
	 * init-function should be called
	 */
	private static $havingSession = false;
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
	 * @static TemplateEngine instance of the TemplateEngine
	 */
	private static $instance = null;
	/**
	 * @static array containing all contexts
	 */
	private static $contexts = array();
	/**
	 * @static integer index of the currently active context
	 */
	private static $currentContext = -1;
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
	 * @static string the path to the base template, relative to the root
	 */
	private static $baseTemplatePath = false;
	/**
	 * @static array cache for files accessed during template processing
	 */
	private static $templateCache = array();
	/**
	 * @static this variable is used to track the name of the template
	 */
	private static $template = '';
	/**
	 * @static this array is used to track all options,
	 */
	private static $options = array();
	/**
	 * @static this array is used to track all default options,
	 */
	private static $defaultOptions = array(
		'plugin_profiling' => false,
		/**
		 * put filename into a comment for all loaded files
		 */
		'debug_files' => false,
		/**
		 * gzip the response in case the browser supports it
		 */
		'gzip' => true,
		/**
		 * dump variables at the end of the response
		 */
		'dump_variables' => false,
		/**
		 * force the file extension of files to tpl (disallow including .php,...)
		 */
		'force_tpl_extension' => true,
		/**
		 * disallow any file access outside the template path
		 */
		'jail_to_template_path' => true,
		/**
		 * enable time capturing
		 */
		'timing' => false,
	);
	/**
	 * @static this array contains all registered event handlers grouped by event
	 */
	private static $handlers = array(
		'set_option' => null,
	);
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
		self :: $contexts[] = array(
			'tpl' => '',
			'ctx' => self :: $variables,
			'templatePath' => '',
			'hit' => 0,
			'miss' => 0,
			'prevContextActivePlugin' => '',
		);
		self :: $currentContext = 0;
	}
	/**
	 * pushConetxt
	 * push a new template context onto the context stack
	 * @param $templateString string the template string to process in this context
	 * @param $context array the context to work on
	 * @return the resulting template string
	 */
	public static function pushContext($templateString, array $context, $templatePath = null) {
		if (null === $templatePath) {
			$templatePath = (self :: $currentContext >= 0) ? self :: $contexts[self :: $currentContext]['templatePath'] : self :: $templatePath;
		}
		self :: $contexts[] = array(
			'tpl' => $templateString,
			'ctx' => $context,
			'templatePath' => $templatePath,
			'hit' => 0,
			'miss' => 0,
			'prevContextActivePlugin' => self :: $activePlugin,
		);
		self :: $currentContext += 1;
		self :: processCurrentContext();
		return self :: endContext();
	}
	/**
	 * endContext
	 * @return current template string
	 */
	public static function endContext() {
		$ctx = array_pop(self :: $contexts);
		self :: $currentContext -= 1;
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
	 * @throws TEPluginRegexInvalidException if the regular expression is invalid
	 * @param $plugin string the name of the plugin to register(must be unique)
	 * @param $regexp string the regular expression whose matches will be handled by the callback
	 * @param $callback callback the function to call with any found match
	 * @return void
	 * @todo document callback interface
	 */
	public static function registerPlugin($plugin, $regex, $callback) {
		if (NULL === @preg_replace($regex, '', '')) {
			throw new TEPluginRegexInvalidException('Error testing the regex of the ' . $plugin . ' plugin: ' . preg_last_error());
		}
		self :: $pluginRegistration[$plugin] = array(
			'regex' => $regex,
			'cb' => $callback,
			'_regex_time' => 0,
			'_total_hit' => 0,
			'_total_try' => 0,
			'_total_decline' => 0,
		);
	}
	/**
	 * unregisterPlugin
	 * unregister the plugin with the given name
	 * @param $plugin string the name of the plugin to unregister
	 * @return void
	 */
	public static function unregisterPlugin($plugin) {
		unset(self :: $pluginRegistration[$plugin]);
	}

	/**
	 * setBaseTemplatePath
	 * sets the path tho a base template, in case the current template requests a
	 * file that does not exist in the current template, but does exist in the base
	 * template the TE uses the template from the base template and acts as if the
	 * template file was included in the current template.
	 * <strong>Note: {TEMPLATE_PATH} will point to the current template, not the base
	 * template, thus references to CSS/... will FAIL when requested from the browser</strong>
	 * @param $path string path of the base template files
	 * @return void
	 */
	public static function setBaseTemplatePath($path) {
		$len = strlen($path);
		if($len && !('/' == $path[$len - 1])) {
			$path .= '/';
		}
		self :: $baseTemplatePath = $path;
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
	 * sets the root path of the application directory seen from the browser!
	 * the root path is available to the templates as {ROOT_PATH}
	 * <strong>Note: this path will always end with a /</strong>
	 * @param $path string the root path of the application as seen by the browser
	 * @return void
	 */
	public static function setRootPath($path) {
		$len = strlen($path);
		if($len === 0 || !('/' == $path[$len - 1])) {
			$path .= '/';
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
		$profile = self :: option('plugin_profiling');
		$recursion_limit = 32;
		$ctx = &self :: $contexts[self :: $currentContext];
		if(strlen($ctx['tpl']) > 0) {
			do {
				$ctx['hit'] = 0;
				$ctx['miss'] = 0;
				foreach (self :: $pluginRegistration as $plugin => &$pdata) {
					self :: $activePlugin = $plugin;
					if ($profile) {
						$pdata['_start'] = microtime(true);
						++$pdata['_total_try'];
					}
					$ctx['tpl'] = preg_replace_callback($pdata['regex'], array('TemplateEngine', 'replace_callback'), $ctx['tpl']);
					if ($profile) {
						$pdata['_regex_time'] += microtime(true) - $pdata['_start'];
					}
				}
			}
			while(($ctx['hit'] > 0) && $recursion_limit--);
		}
	}
	/**
	 * output
	 * process the given template and output results to the browser
	 * @throws TETemplateNotFoundException
	 * @param string $template filename relative to TEMPLATE_PATH
	 * @param boolean $havingSession indicates wheter this runs in a user context or cron context (default: true)
	 * @return void
	 */
	public static function output($template, $havingSession = true) {
		//TODO: allow other content-types (application/json, application/javascript, text/css,...)
		header("Content-Type: text/html; charset=utf-8");
		$result = self :: processTemplate($template, $havingSession);
		//compress using gzip if the browser supports it
		if (self :: option('gzip') && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
			//well if YOU used print/echo before the complete content will be garbage it's YOUR fault!
			header('Content-Encoding: gzip');
			//TODO: ob_start, ob_gz_handler?
			$result = gzencode($result);
		}
		print $result;
	}
	/**
	 * processTemplate
	 * process the given template and output results to the browser
	 * @throws TETemplateNotFoundException
	 * @param string $template filename relative to TEMPLATE_PATH
	 * @param boolean $havingSession indicates wheter this runs in a user context or cron context (default: true)
	 * @return string the processed template string
	 */
	public static function processTemplate($template, $havingSession = true) {
		self :: $havingSession = $havingSession;
		self :: $template = $template;
		self :: captureTime('startTE'); //< init time measurement
		//< if a Info/Warning/Error function is called while running it can not be guaranteed to work correctly
		// -> append it to the Log (with LogMsg)
		self :: $running = true;
		self :: trigger('static_init', self :: Inst()); //< common static (session independent) data will be set here
		if(self :: $havingSession) {
			self :: trigger('init', self :: Inst()); //< do TE initialisation (common data will be set there)
		}
		$tpl = '';
		if (!self :: getFile($template, $tpl)) {
			self :: $running = false;
			throw new TETemplateNotFoundException('TemplateEngine Error: template not found - or not readable! ' . $template);
		}
		$result = self :: pushContext($tpl, self :: $variables);
		self :: captureTime('stopTE');
		$result = str_replace('</body>', (self :: formatLogMessages() . '</body>'), $result);
		self :: $running = false;
		return $result;
	}
	/**
	 * replace_cllback
	 * this callback is called from preg_replace_callback and calls the current plugin
	 * @param array $match match found by preg_replace_callback
	 * @return string the replacement for the match
	 */
	public static function replace_callback(array $match) {
		$profiling = self :: option('plugin_profiling');
		$plugin = &self :: $pluginRegistration[self :: $activePlugin];
		if ($profiling) {
			$plugin['_regex_time'] += microtime(true) - $plugin['_start'];
			++$plugin['_total_hit'];
		}
		$ctx = &self :: $contexts[self :: $currentContext];
		$res = call_user_func($plugin['cb'], $ctx['ctx'], $match);
		if (false !== $res) {
			if (isset($ctx['override'])) {
				$res = self :: pushContext($res, $ctx['override']['ctx'], $ctx['override']['templatePath']);
				unset($ctx['override']);
			}
			++$ctx['hit'];
			if ($profiling) {
				$plugin['_start'] = microtime(true);
			}
			return $res;
		}
		++$ctx['miss'];
		if ($profiling) {
			$plugin['_start'] = microtime(true);
			++$plugin['_total_decline'];
		}
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
		for ($idx = self :: $currentContext; $idx >= 0; --$idx) {
			$ctx = self :: $contexts[$idx]['ctx'];
			if(isset($ctx[$name])) {
				self :: LogMsg('', true, TEMode :: debug);
				$value = $ctx[$name];
				return true;
			}
		}
		self :: LogMsg(' failed', false, TEMode :: debug);
		return false;
	}

	private static function addMsgInternal($class, $msg) {
		$classUpper = strtoupper($class);
		if (!self :: $running) {
			array_push(self :: $variables['TE_' . $classUpper . 'S'], array('CLASS' => $class, 'TEXT' => $msg));
		}
		else {
			self :: LogMsg('[' . $classUpper . ']: ' . $msg, false, TEMode :: error);
		}
	}

	/**
	 * Error
	 * add an error message to display to the user
	 * @param string $error message
	 */
	public static function Error($error) {
		self :: addMsgInternal('error', $error);
	}
	/**
	 * Warning
	 * add a warning message to display to the user
	 * @param string $warning message
	 */
	public static function Warning($warning) {
		self :: addMsgInternal('warning', $error);
	}
	/**
	 * Info
	 * add a info message to display to the user
	 * @param string $info message
	 */
	public static function Info($info) {
		self :: addMsgInternal('info', $error);
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
	public static function header($html) {
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
	public static function addJS($js) {
		$t = "\t".'<script type="text/javascript" src="' . $js . '"></script>'."\n";
		self :: $variables['HEADER_TEXT'] .= $t;
	}
	/**
	 * setArray
	 * compatibility function
	 * @deprecated use set directly
	 * @fixme done for compatibility
	 * @codeCoverageIgnore
	 */
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
		if(!empty(self :: $messages_NotFin)) {
			$oldmsg = implode('', self :: $messages_NotFin);
			self :: $messages_NotFin = array();
			$msg = $oldmsg . $msg;
		}
		self :: trigger('log', $msg, $success, $mode);
	}
	/**
	 * formatLogMessages
	 * format messages logged with LogMsg
	 * @return string generated html
	 */
	private static function formatLogMessages() {
		$html = '';
		self :: LogMsg('<em>formatting messages...</em>', true, TEMode :: debug);
		$succ = array(
			true => '[ <strong class="te_msg_done">done</strong> ]',
			false => '[<strong class="te_msg_failed">failed</strong>]',
		);
		$mode = array(
			TEMode :: debug   => '<strong class="te_msg_dbg">[ DEBUG ]: </strong>',
			TEMode :: warning => '<strong class="te_msg_wrn">[WARNING]: </strong>',
			TEMode :: error   => '<strong class="te_msg_err">[ ERROR ]: </strong>',
			TEMode :: none    => '<strong class="te_msg_non">[  NONE ]: </strong>',
		);
		$msg = '';
		foreach(self :: $messages as $message) {
			if ($message['mode'] >= self :: $mode) {
				$msg .= '<div>';
				$msg .= @$mode[$message['mode']];
				$msg .= '<span>' . $succ[$message['success']] . '</span>';
				$msg .= $message['msg'];
				$msg .= '</div>';
			}
		}
		if (!empty($msg)) {
			$html .= '<div id="te_message_log">' . $msg . '</div>';
		}
		return $html;
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

	private static function printPluginProfiling() {
		print '<hr /><div style="background-color:#888;"><table style="margin: 0 auto;';
		print 'border:1px solid green;width:70%;"><thead><tr><th>Plugin</th>';
		print '<th>Regex matching time</th><th>Tries</th><th>Hit</th><th>Decline</th></tr></thead>';
		$totalTime = 0;
		$totalTry = 0;
		$totalHit = 0;
		$totalDecline = 0;
		foreach(self :: $pluginRegistration as $name => $plugin) {
			$totalTime += $plugin['_regex_time'];
			$totalTry += $plugin['_total_try'];
			$totalHit += $plugin['_total_hit'];
			$totalDecline += $plugin['_total_decline'];
			print '<tr>';
			print '<td>' . $name . '</td>';
			print '<td>' . ($plugin['_regex_time'] * 1000) . 'ms</td>';
			print '<td>' . $plugin['_total_try'] . '</td>';
			print '<td>' . $plugin['_total_hit'] . '</td>';
			print '<td>' . $plugin['_total_decline'] . '</td>';
			print '</tr>';
		}
		print '<tr>';
		print '<td>Total</td><td>' . ($totalTime * 1000) . '</td>';
		print "<td>$totalTry</td><td>$totalHit</td><td>$totalDecline</td>";
		print '</tr></table></div>';
	}

	public static function shutdown_function() {
		if (self :: option('dump_variables')) {
			self :: dumpVariables();
		}
		if (self :: option('timing')) {
			self :: printTimingStatistics();
		}
		if (self :: option('plugin_profiling')) {
			self :: printPluginProfiling();
		}
	}

	/**
	 * forceMode
	 * force the given log level, ignore Templates setting the level differently
	 * @param $mode @see TEMode
	 * @return void
	 */
	public static function forceMode($mode) {
		self :: $mode = $mode;
		self :: $mode_forced = true;
	}

	/**
	 * option
	 * the option function can be used to get or set the value of an option.
	 * @param string $name name of the option
	 * @param mixed $value optional value of the option, if specified this function is a setter
	 * @return mixed current value of the option
	 */
	public static function option($name, $value = null) {
		if (func_num_args() > 1) {
			self :: trigger('set_option', $name, $value);
		}
		if (isset(self :: $options[$name])) {
			return self :: $options[$name];
		}
		return self :: $defaultOptions[$name];
	}

	private static function _set_option($name, $value) {
		self :: $options[$name] = $value;
	}

	/**
	 * on
	 * register an event handler for the given event
	 */
	public static function on($event, $callback, $phase = TEEventPhase :: inform) {
		if (!isset(self :: $handlers[$event]) || null === self :: $handlers[$event]) {
			self :: $handlers[$event] = new TEEventRegistration();
		}
		self :: $handlers[$event]->on($phase, $callback);
	}

	/**
	 * trigger
	 * trigger the given event, calling all event handlers in turn
	 * @param string $event name of the event to trigger
	 * @return boolean false if any of the event handlers returned false
	 */
	public static function trigger($event) {
		$args = func_get_args();
		$event_name = $args[0];
		$args = array_splice($args, 1);
		$result = true;
		if (isset(self :: $handlers[$event_name])) {
			$evt = self :: $handlers[$event_name];
			foreach($evt->get(TEEventPhase :: evaluate) as $callback) {
				$result = $result && false !== call_user_func_array($callback, $args);
			}
			if ($result) {
				foreach($evt->get(TEEventPhase :: execute) as $callback) {
					call_user_func_array($callback, $args);
				}
				foreach($evt->get(TEEventPhase :: inform) as $callback) {
					call_user_func_array($callback, $args);
				}
			}
		}
		return $result;
	}

	/**
	 * setMode
	 * set the log level to the given mode, only if the mode haas not been forced otherwise
	 * @param $mode @see TEMode
	 * @return void
	 */
	public static function setMode($mode) {
		if (!self :: $mode_forced) {
			self :: $mode = $mode;
		}
	}

	/**
	 * setFileDebugMode
	 * enable or disable file debugging mode
	 * @param boolean $mode set to true to enable comment insertion
	 * @deprecated
	 */
	public static function setFileDebugMode($mode) {
		self :: option('debug_files', $mode);
	}

	/**
	 * setForceTplExtension
	 * enable or disable file extension sanity check
	 * @param boolean $mode set to true to enable sanity check
	 * @deprecated
	 */
	public static function setForceTplExtension($mode) {
		self :: option('force_tpl_extension', $mode);
	}

	/**
	 * setJailToTemplatePath
	 * enable or disable file accessing files outside the set template path
	 * @param boolean $mode set to true to enable security check
	 * @deprecated
	 */
	public static function setJailToTemplatePath($mode) {
		self :: option('jail_to_template_path', $mode);
	}

	/**
	 * noGzip
	 * disallow gzipping
	 * @return void
	 * @deprecated
	 */
	public static function noGzip() {
		self :: option('gzip', false);
	}

	/**
	 * enableTiming
	 * set capturing of timing information to on
	 * @return void
	 * @deprecated
	 */
	public static function enableTiming() {
		self :: option('timing', true);
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
			self :: option('gzip', false);
		}
	}

	/**
	 * reroute non static calls to the static functions
	 * @throws Exception
	 * @param string $method
	 * @param array $args
	 */
	public function __call($method, $args) {
		if (function_exists(array('TemplateEngine', $method))) {
			return call_user_func_array(array('TemplateEngine', $method), $args);
		}
		throw new Exception("The method 'TemplateEngine::$method' does not exist!");
	}

	private static function doGetFile($templatePath, $name, &$content) {
		//TODO: move the cache one level up or keep on this level??
		$fname = realpath(self :: $rootPath . $templatePath . $name);
		$content = '';
		self :: LogMsg('[getFile]: <em>"' . $name . '"</em> ', true, TEMode :: debug, false);
		if (!isset(self :: $templateCache[$fname])) {
			if (!file_exists($fname) || !is_readable($fname)) {
				return array(false, 'file not found!', false, TEMode :: error, true);
			}
			if (self :: option('force_tpl_extension') && !preg_match('/\.tpl$/', $name)) {
				return array(false, 'invalid file', false, TEMode :: error, true);
			}
			if (self :: option('jail_to_template_path')) {
				$tplpath = realpath(self :: $rootPath . $templatePath);
				if (0 !== strncmp($tplpath, $fname, strlen($tplpath))) {
					return array(false, 'access denied', false, TEMode :: error, true);
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
		if (self :: option('debug_files')) {
			$fname = str_replace(realpath(self :: $rootPath) . '/', '', $fname);
			$content = "<!-- start $fname -->\n" . $content . "<!-- end $fname -->\n";
		}
		return array(true);

	}

	private static function addOverride($path) {
		$ctx = &self :: $contexts[self :: $currentContext];
		$ctx['override'] = array(
			'templatePath' => $path,
			'ctx' => array(
				'TEMPLATE_PATH' => self :: $rootPath . $path,
			),
		);
	}
	/**
	 * getFile
	 * read the file (log that) and get the content
	 * @param string $name filename(relative to TEMPLATE_PATH)
	 * @param string $content passed by reference contains the file content on success, unmodified otherwise
	 * @return boolean true if the file was found, readable and loaded
	 */
	public static function getFile($name, &$content) {
		//TODO: move template caching logic here
		//TODO: let possible getFile plugins specify if their result can be cached
		$result = self :: doGetFile(self :: $templatePath, $name, $content);
		if (true !== $result[0] && self :: $baseTemplatePath) {
			$result = self :: doGetFile(self :: $baseTemplatePath, $name, $content);
			if (true === $result[0]) {
				self :: addOverride(self :: $baseTemplatePath);
			}
		}
		elseif (self :: $contexts[self :: $currentContext]['templatePath'] !== self :: $templatePath) {
			self :: addOverride(self :: $templatePath);
		}
		if (true !== $result[0]) {
			self :: LogMsg($result[1], $result[2], $result[3], $result[4]);
		}
		return $result[0];
	}

	/**
	 * @deprecated
	 */
	public static function dumpVariablesOnExit() {
		self :: option('dump_variables', true);
	}

	public static function dumpVariables() {
		$template = self :: $template;
		print '<pre style="text-align:left;background-color:white;">';
		print "Template: $template\n";
		print "Available Template-Variables:\n";
		print htmlentities(print_r(self :: $variables, true));
		print "</pre>";
	}

	public static function _te_init() {
		new TemplateEngine(); //required! as the first instance clears and initializes the TE
		register_shutdown_function(array('TemplateEngine', 'shutdown_function'));
		self :: on('set_option', array('TemplateEngine', '_set_option'), TEEventPhase :: execute);
		self :: on('set_option', array('TemplateEngine', '_set_option_handler'), TEEventPhase :: inform);
		self :: on('log', array('TemplateEngine', '_log'), TEEventPhase :: execute);
		if (function_exists('TE_static_setup')) {
			self :: on('static_init', array('TemplateEngine', '_static_init'), TEEventPhase :: execute);
		}
		if (function_exists('TE_setup')) {
			self :: on('init', array('TemplateEngine', '_init'), TEEventPhase :: execute);
		}
	}

	private static function _log($msg, $success, $mode) {
		self :: $messages[] = array(
			'mode' => $mode,
			'success' => $success,
			'msg' => $msg,
		);
	}

	private static function _static_init($te) {
		TE_static_setup();
	}

	private static function _init($te) {
		TE_setup();
	}

	private static function _set_option_handler($name, $value) {
		switch ($name) {
			case 'dump_variables':
			case 'plugin_profiling':
			case 'timing':
				if ($value) {
					self :: option('gzip', false);
				}
				break;
			case 'force_tpl_extension':
			case 'jail_to_template_path':
				if (!self :: option('force_tpl_extension') && !self :: option('jail_to_template_path')) {
					self :: LogMsg('Security settings disabled, use with extreme caution!', false, TEMode :: error);
				}
				break;
			default:
				break;
		}
	}
};

// #######################################################################################
// setup the use of the TemplateEngine, handle GET parameters, setup php-error-handler,...
// #######################################################################################
// setup TEmplateEngine environment
TemplateEngine :: _te_init();
TemplateEngine :: captureTime('TEincluded'); //< page start init
TemplateEngine :: useTEErrorHandler(!isset($_GET['force_def_err_handler']));

// force debug level if 'force_debug' is set in $_GET
if(isset($_GET['force_debug'])) {
	TemplateEngine :: forceMode(TEMode :: debug);
}
// activate timing information if 'show_timing' is set in $_GET
if(isset($_GET['show_timing'])) {
	TemplateEngine :: option('timing', true);
}
// activate file debugging if 'debug_files' is set in $_GET
if(isset($_GET['debug_files'])) {
	TemplateEngine :: option('debug_files', true);
}
//don't gzip if impossible ;)
if (!function_exists('gzencode')) {
	TemplateEngine :: option('gzip', false);
}
// dump name and value of all set template variables
if(isset($_GET['te_dump'])) {
	TemplateEngine :: option('dump_variables', true);
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
function TE_php_err_handler($errno, $errstr, $errfile = '', $errline = '', $errcontext = array()) {
	TemplateEngine :: LogMsg("#$errno: $errstr @$errfile($errline)", false, TEMode::error);
}

/**
 * TE_php_exception_handler
 * this function prints out a descriptive message if an unhandled exception is encountered
 * @param object $exception the exception object
 */
function TE_php_exception_handler($exception) {
	print "Unhandled Exception: " . $exception->getMessage();
}

if (!isset($_GET['force_def_exception_handler'])) {
	set_exception_handler('TE_php_exception_handler');
}

if (isset($_GET['te_profile'])) {
	TemplateEngine :: option('plugin_profiling', true);
}
//EOF
