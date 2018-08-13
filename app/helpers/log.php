<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class log
{

	//Report debug if over 3000 milliseconds for php to run
	const MAX_LOAD_TIME = 3000;

/**
| -------------------------------------------------------------------------
| Error: prepends user and org to error msg
| -------------------------------------------------------------------------
| @param $msg, message to write to log file
| @return $msg, for reuse within code
*/
	function error($msg)
	{
		return self::write($msg, 'error');
	}

/**
| -------------------------------------------------------------------------
| Info: prepends user and org to info msg
| -------------------------------------------------------------------------
| @param $msg, message to write to log file
| @return $msg, for reuse within code
*/
	function info($msg)
	{
		return self::write($msg, 'info');
	}

/**
| -------------------------------------------------------------------------
| Write: prepends user and org to msg
| -------------------------------------------------------------------------
| @param $msg, message to write to log file
| @param $type, options info, error, debug
| @return $msg, for reuse within code
*/
	function write($msg, $type = 'info')
	{
		$this->load->library('log');

		if ( ! is_string($msg)) $msg = print_r($msg, true);

		$this->log->write_log($type, ltrim(data::get('user_name').' '.data::get('org_name').'. ').$msg);

		return $msg;
	}

/**
| -------------------------------------------------------------------------
| Start //TODO: make compatible with AHRQ and store in DB
| -------------------------------------------------------------------------
|
|
|
*/
	function start($msg)
	{
		$this->load->library('user_agent');

		if ($this->agent->is_robot())
		{
			return;
		}

		// If page load we want to record the URI and the execution time.  We don't want
		// to line break because we want the page load time to be appeneded to this line
		// Lets not record robots
		if (text::has($msg, 'File loaded') && ! text::has($this->uri->uri_string(), 'parse'))
		{
			$db  = ceil($this->db->benchmark * 1000);

			$php = ceil($this->benchmark->elapsed_time('total_execution_time_start') * 1000) - $db;

			$cpu = @exec("ps -A -o %cpu | awk '{s+=$1} END {print s}'");

			$who = data::get('contact_name') .'  @  '.data::get('org_name').': ';

			if ($who == '  @  : ')
			{
				$ip = $this->input->ip_address();

				$who = ($ip == HOST_IP_ADDRESS || $ip == '127.0.0.1') ? '' : $ip.': ';
			}

			$msg =  $who . $uri." (cpu: {$cpu}%) (db: {$db}ms) (php: {$php}ms) ";

			return self::write(($db + $php) > MAX_LOAD_TIME ? 'error' : 'info', $msg);
		}
	}

/**
| -------------------------------------------------------------------------
| Stop
| -------------------------------------------------------------------------
|
|
|
*/
	function stop($msg)
	{
		$this->load->library('user_agent');

		if ($this->agent->is_robot())
		{
			return;
		}

		//If this is the page load beacon (about/beacon/load:milliseconds)  then append to the current line and start a new line
		if (text::has($msg, 'load:'))
		{
			return self::write('info', "$msg\n");
		}
	}

} //END OF CLASS AND FILE