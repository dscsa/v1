<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Security extends the built-in CI Security Class
 *
 *
 *
 */
class MY_Security extends CI_Security
{

/**
	 * Redirect to current page (maybe showing login screen)
	 * rather than showing terminal CSRF Error
	 *
	 * @access	public
	 * @return	null
	 */
	function csrf_show_error()
	{
		header( 'Location: '.$_SERVER['HTTP_REFERER'] ) ;
		exit; //Avoids errors from security class continuing to run
	}

}

/* End of file MY_Security.php */