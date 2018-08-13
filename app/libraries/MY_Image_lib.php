<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Image_lib extends the built-in CI Image Lib Class
 *
 */
class MY_Image_lib extends CI_Image_lib
{
	function image_save_gd($resource)
	{
		$image_types = ['gif' => 1, 'jpg' => 2, 'jpeg' => 2, 'png' => 3];
		
		$this->image_type = $image_types[$this->new_type];
		
		parent::image_save_gd($resource);
	}
}

/* End of file MY_Image_lib.php */