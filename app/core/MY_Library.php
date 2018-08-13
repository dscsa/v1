<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//Elegant laravel inspired 'factory method pattern' to create libraries with chainable methods.

class MY_Library
{
	// Non-static since we need separate vars for each instance of the class that's created
	protected $string = '';

	// Static since only need one reference to CI no matter how many instances are created
	static $ci;

/**
| -------------------------------------------------------------------------
| Factory: uses callStatic with protected methods to create new object
| -------------------------------------------------------------------------
| Force creation of new object to be chained by declaring library function
| protected so all method calls must either goto __callStatic which creates
| the new object (factory) or __call which forwards the call past protected
*/
	static function __callStatic($name, $args)
	{
		return call_user_func_array([new static, $name], $args);
	}

	function __call($name, $args)
	{
		return call_user_func_array([$this, $name], $args);
	}
	
/**
| -------------------------------------------------------------------------
| Trigger: methods can add to $string which will trigger when displayed
| -------------------------------------------------------------------------
| Rather than ending a method chain with a trigger, we can have each method
| append their result to the class variable $string before returning $this.
| We then use the __toString magic method to display the string in the view
| @note subclass must define protected $string variable within it
*/
	function __toString()
	{
		return $this->string;	
	}
	
/**
| -------------------------------------------------------------------------
| Codeigniter: allows use of CI instance within chained methods
| -------------------------------------------------------------------------
| pass through unset properties to the CI super object.  Don't put this in
| constructor since we want libraries that don't use the factory method of
| this class to still be able to use CI functionality
*/
	function __get($name)
	{
		if ( ! self::$ci) self::$ci = & get_instance();
		
		// If propety is not set, we don't want to throw an error
		return isset(self::$ci->$name) ? self::$ci->$name : null;
	}
	
} //END OF CLASS AND FILE