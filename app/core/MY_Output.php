<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Outpur extends the built-in CI Output Class
 *
 *
 */
class MY_Output extends CI_Output
{
	var $final_output;
/*
* No Cache 
* Precent Caching of Dynamic PDFs
*
*/		
	function no_cache()
	{ 
		$this->set_header("Cache-Control: no-store, no-cache, must-revalidate"); 
		$this->set_header("Pragma: no-cache"); 
		$this->set_header("Expires: -1"); 
	}
		
}

/* End of file MY_form_validation.php */