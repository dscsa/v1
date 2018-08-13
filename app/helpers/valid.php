<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class valid
{
/**
| ---------------------------------------------------------
| And Submit: form must be valid & correct submit button pressed
| ---------------------------------------------------------
| @param $value, case insensitive value attribute to check
| @param $group, override class/method page validation rules
| @note helpful to confirm only on a cetain post type
| @return bool
*/
	function and_submit($value = 'Submit', $group = '')
	{
		if ( ! self::form($group)) return false;

		return self::submit($value);
	}

/**
| ---------------------------------------------------------
| Or Submit: either valid form or correct submit button pressed
| ---------------------------------------------------------
| @param $value, case insensitive value attribute to check
| @param $group, override class/method page validation rules
| @note helpful to skip validation on a cetain post type
| @return bool
*/
	function or_submit($value = 'button', $group = '')
	{
		if (self::form($group)) return true;

		return self::submit($value);
	}

/**
| ---------------------------------------------------------
| Submit: has a submit button with given value been pressed
| ---------------------------------------------------------
| @param $value, case insensitive value attribute to check
| @note helpful to differentiate what type of post occurred
| @return bool
*/
	function submit($value)
	{
		return strtolower(data::post('button')) == strtolower($value);
	}

/**
| ---------------------------------------------------------
| Form: check validation rules for class/method in config file
| ---------------------------------------------------------
| @param $group, override key used to search the config array
| @note Default group for run is usually the entire URI.  To ignore
| parameters we default the group of rules to the controller/function
| if two submit buttons we may need to specify which one submits the form
| @return bool
*/
	function form($group = '')
	{
		$this->load->library('form_validation');

		return $this->form_validation->run( $group ?: $this->router->base());
	}

} //END OF CLASS AND FILE