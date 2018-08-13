<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//Add CI helper specific functionality to MY_Library

class MY_Helper extends MY_Library
{
	
/**
| -------------------------------------------------------------------------
| Loads the corresponding CI helper
| -------------------------------------------------------------------------
| Assumes this class shares the same names as the helper
*/
	function __construct()
	{
		$this->load->helper(get_called_class());
	}

/**
| -------------------------------------------------------------------------
| Look in CI helper if class does not have specified method
| -------------------------------------------------------------------------
| May be called from class method or from callStatic factory method
*/
	function __call($name, $args)
	{
		if ( ! method_exists($this, $name))
		{
			$this->string .= call_user_func_array(get_called_class()."_$name", $args);
	
			return $this;
		}
		
		return parent::__call($name, $args);
	}
	
	
} //END OF CLASS AND FILE