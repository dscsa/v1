<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
| -------------------------------------------------------------------------
| PHP Helper
| -------------------------------------------------------------------------
|
| Normal CI helpers pollute the PHP name space and must "always load".  For
| this reason all helpers except for this one are static classes.  This class
| should be limited to functions that you think PHP ought to have natively
|
*/


/**
| ---------------------------------------------------------
| If Set: returns var if not empty, otherwise default
| ---------------------------------------------------------
| @param mixed argument by reference to check for emptiness
| @param mixed default value
| @return mixed first arg if set, else default
*/
	function ifset( & $var, $default = null)
	{
		return $var ?: $default;
	}
	
/**
| ---------------------------------------------------------
| Debug: print_r an array with <pre></pre> for formatting
| ---------------------------------------------------------
| @param array to be debugged
| @return echo formatted array
*/
	function debug($array)
	{
	  echo '<pre>';
	  print_r($array);
	  echo '</pre>';
	  echo html::br(2);
	}

/**
| ---------------------------------------------------------
| Backtrace: list of previously called files with lines #s
| ---------------------------------------------------------
| @param $i, if a specific line is needed, return that line
| @return echo html formatted text
*/
	function backtrace($i = '')
	{
	  $backtrace = debug_backtrace();
	  
	  if ($i) return "Line: ".ifset($backtrace[$i]['line'])." in ".ifset($backtrace[$i]['file']);
	  
	  foreach ($backtrace as $call)
	  {
		echo "Line: ".ifset($call['line'])." in ".ifset($call['file']).html::br(1);
	  }
	  
	  echo html::br(1);
	}

/**
| ---------------------------------------------------------
| Memory: memory usage with units and line breaks
| ---------------------------------------------------------
| @param $string, if true will return string rather than echo
| @param $digits, precision to round too.
| @return echo html formatted memory usage
*/
	function memory($string = false, $digits = 2)
	{
		$size = memory_get_usage(true);
		
		$unit = ['b','kb','mb','gb','tb','pb'];
		
		$mem = @round($size/pow(1024,($i=floor(log($size,1024)))), $digits).' '.$unit[$i];
		
		if ($string)
		{
			return $mem;
		}
		
		echo $mem.html::br(2);
	}
	
/* End of file general_helper.php */