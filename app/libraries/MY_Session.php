<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Session extends the built-in CI Session Class
 *
 *
 *
 */
class MY_Session extends CI_Session
{
	function _flashdata_mark()
	{
		$ci =& get_instance();

		if ('ajax' != $ci->uri->segment(1) && ! $ci->input->is_ajax_request())
		{
			parent::_flashdata_mark();
		}
	}
}

/* End of file MY_Session.php */