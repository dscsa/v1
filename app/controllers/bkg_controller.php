<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bkg_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  _remap
| -------------------------------------------------------------------------
|
| Magic codeigniter class is used for security since no Cron functions are
| accessible publically.  With some clever logic the main function can be
| called with bkg/password while individuals functions can be called
| with bkg/password/class/function/[arg1]/[arg2]. Throttle defaults to
| false when called this way. Incorrect password are logged
|
*/
	function _remap($password, $args = [])
	{
		$method = $args ? '_run' : '_cron';

		if ( ! in_array(end($args), ['true', 'false']))
		{
			$args[] = 'false'; //throttle = false
		}

		if ($password != secure::key('cron'))
		{
			show_404(log::error("CRON password $password incorrect"));
		}

		call_user_func_array([ & $this, $method], $args);
	}


/**
| -------------------------------------------------------------------------
|  _cron
| -------------------------------------------------------------------------
|
| Base Cron Job does the work of timing since this is easier to
| change and debug than multiple cron jobs each with different
| timing configurations.  Also centralizes password checking
|
*/
	function _cron()
	{
		//Typically run at the beginning of every hour
		//but can change this within crontab on server
		bkg::donation('track');

		// Run daily at 3am GMT / 7pm PT / 4pm ET. Fedex
		// pickups should have happened & can be rescheduled
		if($daily = gmdate('H') == '03')
		{
			log::info('CRON - Daily');
		   // ":" delimiter does not work with ec2 class :-( so dont include time stamp with description's date
			bkg::ec2('CreateSnapshot', ["VolumeId" => secure::key('aws_volume'), "Description" => 'CRON'.date('Y-m-d')]);

			bkg::admin('digest');
		}

		//Run on Monday at 9am GMT
		//$weekly =  ($daily && gmdate('l') == 'Monday');

		//Run on first day of the month at 9am GMT
		if($monthly = $daily AND gmdate('d') == '01')
		{
			log::info('CRON - Monthly');

			bkg::item('update');

			bkg::item('price', 'medicine');
		}

		echo "CRON completed successfully";
	}

	function _run()
	{
		$args = func_get_args();

		$class = array_shift($args);

		$func = array_shift($args);

		$throttle = array_pop($args);

		$pid = getmypid();

		log::info("BKG - $class::$func $pid".($throttle == 'true' ? ' throttled' : ''));

		//throttling for ec2 micro instance 1/9 split runs indefinitely
		if ($throttle == 'true') exec
		("
			while true; do

			   sleep 1

			   if ! kill -STOP $pid >/dev/null 2>&1; then break; fi

			   sleep 9

			   if ! kill -CONT $pid >/dev/null 2>&1; then break; fi

		   done > /dev/null &
		");

		// Not sure why security doesn't clear this, but
		// it was causing an error in unserialize which
		// corrupted the data being sent.
		unset($_POST[$this->security->csrf_token_name]);

		foreach ( (array) $this->input->post() as $arg)
		{
			$args[] = unserialize(urldecode($arg));
		}

		if(file_exists(APPPATH.'models/'.$class.EXT))
		{
			$this->load->model($class);

			$class = & $this->$class;
		}

		return call_user_func_array([$class, $func], $args);
	}
}

class Bkg
{

/**
| -------------------------------------------------------------------------
| Call Static Magic Method
| -------------------------------------------------------------------------
|
| func specifies in which model the method resides. Sets throttle to true
|
*/
	static function __callStatic($class, $args)
	{
		$func = array_shift($args);

	   $ci =& get_instance();

		$csrf = $ci->security->csrf_token_name.'='.$ci->security->csrf_hash;

		$data = "--cookie $csrf --data $csrf";

		foreach ($args as $i => $arg)
		{
			$data .= " --data $i=".urlencode(serialize($arg));
		}

		//Use local IP because site_url was resolving to wrong IP
		$url = '127.0.0.1/bkg/'.secure::key('cron')."/$class/$func/false";

		exec("curl -s $data $url > /dev/null &");

		return true;
	}

}

/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
