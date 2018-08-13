<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class text
{

/**
| ---------------------------------------------------------
| Get: retrieve line from lang file and parse it with args
| ---------------------------------------------------------
| @param $text, mixed or lang line with parsable vars {0},{1}
| @param $args, array of strings or multiple parameters to be
| parsed into $line as specified by their keys or order
| @return parsed string
*/
	function get($text, $args = array())
	{
		$this->load->library('lang');

		$this->load->library('parser');
		
		//Cleverly get arguments not passed as an array
		if (func_num_args() > 2)
		{
			$args = array_slice(func_get_args(), 1);
		}
	
		$parse = $this->lang->line($text) ?: $text;
		
		//capitalize arg if it appears at beginning of sentence
		if ($parse[0] == '{') $args[0] = ucfirst($args[0]);
		
		// cannot load libraries from the view normally so get_instance
		return get_instance()->parser->parse_string($parse, (array) $args, true);
	}
	
/**
| -------------------------------------------------------------------------
| Plural: prepends num to word that is made plural if num != 1
| -------------------------------------------------------------------------
| @param $num, number that determines if word is made plural or not
| @param $noun, the word that is made plural
| @param $prepend, if true then prepends num to noun with a &nbsp;
*/
	function plural($num, $noun, $prepend = true)
	{
		$this->load->helper('inflection');
	
		if ($num != 1)
		{
			$noun = plural($noun);
		}
	  
		return $prepend ? "$num&nbsp;$noun" : $noun;
	}

/**
| -------------------------------------------------------------------------
| Dollar: formats a number as American currency prepending a '$'
| -------------------------------------------------------------------------
| @param $value, numeric value to be rounded to two decimals
| @param $default, text to be displayed if $value evaluates to 0
*/
	function dollar($value, $default = 'None')
	{
		return $value ? '$'.round($value, 2) : $default;
	}

/**
| ---------------------------------------------------------
| Has: does haystack contain needle
| ---------------------------------------------------------
| @param $haystack, string to be searched
| @param $needle, string or array of strings to find
| @return bool
*/
	function has($haystack, $needles = [])
	{
		foreach ( (array) $needles as $needle)
		{
			if ( strpos($haystack, $needle) !== false) return true;
		}
		
		return false;
	}
	
/**
| ---------------------------------------------------------
| Between: return portion of string between two delimiters
| ---------------------------------------------------------
| @param $haystack, string to be segmented by delimiters
| @param $l_delim, left delimiter if found else start of string
| @param $r_delim, right delimiter if found else end of string
| @note returns whole string if delimiters not found
| @return string
*/
	function between($haystack, $l_delim = '/', $r_delim = '_')
	{
		return reset(explode($r_delim, end(explode($l_delim, $haystack))));
	}
	
} //END OF CLASS AND FILE