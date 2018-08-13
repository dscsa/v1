<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class to
{

/**
| ---------------------------------------------------------
| Url: redirect to url, including back and temp functionality
| ---------------------------------------------------------
| @param $to, url of redirect, priority in descending order
| is $_GET[to], $to parameter, current url
| @param $temp, if true sets $_GET[to] to current url so user
| will come back to this page after a temporary redirection
| Example would be to::url(login, true) which will automatically
| send user back to current page after a successful login.
| @return redirect header
*/
	function url($to = '', $temp = false, $bust = false)
	{
		$uri = $this->uri->uri_string().$this->router->get_string();

		$to = $to ?: $uri;

		if ($temp)
		{
			$to .= '?to='. str_replace(base_url(), '', $uri);
		}
		else
		{
			$to = $this->input->get('to') ?: $to;
		}

		if ($bust)
		{
			echo "<script>top.location.replace('/$to');</script>";
		}
		else redirect($to);
	}

/**
| ---------------------------------------------------------
| Info: url redirect with message wrapped in an info div
| ---------------------------------------------------------
| @param $lang, array of strings to parse into $line in order
| @param $line, string or lang with parsable vars {0},{1}, etc
| @param $url, url of redirect, priority in descending order
| is $_GET[to], $url parameter, current url
| @return redirect header with flash data
*/
	function info($lang = array(), $line = 'to_default', $url = '', $bust = false)
	{
		self::send($lang, $line, $url, 'avia-color-blue', 'Info', $bust);
	}

/**
| ---------------------------------------------------------
| Alert: url redirect with message wrapped in an error div
| ---------------------------------------------------------
| @param $lang, array of strings to parse into $line in order
| @param $line, string or lang with parsable vars {0},{1}, etc
| @param $url, url of redirect, priority in descending order
| is $_GET[to], $url parameter, http referer, current url
| @referrer is especially good for page permission errors
| in order to send the user back from where they came
| @return redirect header with flash data
*/
	function alert($lang = array(), $line = 'to_default', $default = '', $bust = false)
	{
		self::send($lang, $line, (empty($_SERVER['HTTP_REFERER']) or text::has($_SERVER['HTTP_REFERER'], '/login?to=')) ? $default : $_SERVER['HTTP_REFERER'], 'avia-color-red', 'Alert', $bust);
	}

/**
| ---------------------------------------------------------
| Send: url redirect with message in div of specified class
| ---------------------------------------------------------
| @param $lang, array of strings to parse into $line in order
| @param $line, string or lang with parsable vars {0},{1}, etc
| @param $url, url of redirect, priority in descending order
| is $_GET[to], $url parameter, current url
| @param $class, class of the div to wrap the message in
| @return redirect header with flash data
*/
	function send($lang = array(), $line = 'to_flash', $url = '', $class = '', $title = '', $bust = false)
	{
		$line = is_array($lang) ? text::get($line, $lang) : $lang;

		data::send('__to__', html::box($line, $class, $title));

		if ('info' == $class || 'error' == $class) log::$class($line);

		self::url($url, false, $bust);
	}

/**
| ---------------------------------------------------------
| Sent: retrieve messages sent by send, info, or error
| ---------------------------------------------------------
| @return string of flash data
*/
	function sent()
	{
		return data::sent('__to__');
	}

} //END OF CLASS AND FILE