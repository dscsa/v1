<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Input extends the built-in CI Input Class
 *
 *
 */
class MY_Input extends CI_Input
{
	
	// --------------------------------------------------------------------

	/**
	* Clean Keys
	*
	* Same as original but adds * for NDC.
	*
	* @access	private
	* @param	string
	* @return	string
	*/
	
	function _clean_input_key($str)
	{
		if ( ! preg_match("/^[a-z0-9\*:_\/-]+$/i", $str))
		{
			log::error('Disallowed Key Characters: '.$str);
			exit('Disallowed Key Characters: '.$str);
		}

		// Clean UTF-8 if supported
		if (UTF8_ENABLED === TRUE)
		{
			$str = $this->uni->clean_string($str);
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	* Clean Input Keys
	*
	* Just in case user has copied and pasted email which is using the stop link feature
	* We remove zero width spaces that might be present which would get caught by the core
	* UTF8 class which deletes all non-ascii
	*
	* @access	private
	* @param	string
	* @return	string
	*/
	
	
	function _clean_input_data($var)
	{
		if ( ! is_array($var))
		{
			preg_replace('/[^\x00-\x7F]/S', '', $var);
		}
		
		return parent::_clean_input_data($var);
	}
		
}

/* End of file MY_Input.php */