<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Router extends the built-in CI Routes
 *
 */
class MY_Router extends CI_Router
{
	// --------------------------------------------------------------------

	/**
	 * Set the class name with _controller suffix so that models don't need to have  suffix
	 *  to avoid name collision by sharing the same class as the controller
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */

	function _validate_request($segments)
	{
		if ($segments)
		{
			$segments[0] .= $this->config->item('controller_suffix');
		}
		
		return parent::_validate_request($segments);
	}
	
	function controller()
	{
		return str_replace($this->config->item('controller_suffix'), '', $this->fetch_class());
	}
	
	function base()
	{
		return $this->controller().'/'.$this->fetch_method();
	}
	
	//Used by Form Helper and Result Helper
	function get_string($override = array())
	{
		if($get = array_merge(get_instance()->input->get() ?: array(), $override))
		{
			unset($get['p']); //if user searches we want to start back at page 1
			
			return '?'.http_build_query($get);
		}
		
		return null;
	}
}