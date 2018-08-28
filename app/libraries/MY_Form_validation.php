<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Form_Validation extends the built-in CI Form Validation Class
 *
 * This class adds new prepping and checking capabilities to the
 * built-in CI form_validation class.
 *
 * @author Casey McLaughlin
 * @package CIHeadStart
 * @link http://code.google.com/p/ciheadstart
 */
class MY_Form_validation extends CI_Form_validation
{
	protected $_error_prefix		= '<div class="red inline-block">';
	protected $_error_suffix		= '</div>';
	public $_error_array			   = array();  //Validation emails needs this
	public $_field_data 			   = array();  //Data helper needs to access
	public $_config_rules 			= array();  //Form helper needs to access
 /**
| -------------------------------------------------------------------------
|  Address - Helper Function
| -------------------------------------------------------------------------
|
| Custom validation callback for user registration and updating
| a users profile. Checks FedEx API to make sure address is in
| database. Required otherwise shipping label generation through
| FedEx could fail.
|
| @param string street  is passed because the callback is placed
| on that field.  However, callback could be placed on any field
| because it uses the post data from street, city, zip to determine
| if the address is valid or not.  It will accept an address if FedEx
| "accepts the address with changes" because this means that FedEx
| will know what changes it needs to make when producing the
| shipping label.  Could cause problems if more couriers introduced.
|
*/

    function address($street)
    {
		$ci = & get_instance();

		$city  = $ci->input->post('city');
		$state = $ci->input->post('state');
		$zip   = $ci->input->post('zipcode');

		// Since this could be run with ajax before all fields are
		// complete then lets give the user the benefit of the doubt
		// and say the address is true until the last field is done.
		if ($ci->input->is_ajax_request())
		{
			return true;
		}

		$ci->load->library('fedex');

		$valid_zip   = $ci->fedex->address($street, NULL, NULL, $zip);
		$valid_city  = $ci->fedex->address($street, $city, NULL, NULL);
		$valid_state = $ci->fedex->address($street, $city, $state, NULL);

		if ($valid_zip AND ! $valid_city)
		{
			$this->set_message('address', $ci->lang->line('address').". Check your street and city");
		}
		else if ( ! $valid_zip AND $valid_city)
		{
			$this->set_message('address', $ci->lang->line('address').". Check your street and zipcode");
		}
		else if ( ! $valid_state AND $valid_city)
		{
			$this->set_message('address', $ci->lang->line('address').". Check your state");
		}

		return ($valid_zip AND ($valid_city OR $valid_state));
    }

/**
| -------------------------------------------------------------------------
|  Check Password - Helper Function
| -------------------------------------------------------------------------
|
| Regular expression to validate a password.
| Password must contain 6 characters and no more than 20,
| at least one upper case letter (A-Z), one lower case letter (a-z),
| and one numeric character (0-9).
| The other characters may be from the set A-Za-z0-9$#_\ plus blank.
|
| @param string password
|
*/

 	function new_password($password)
	{
		//(bool) preg_match("/^.*(?=.{6,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", $password);
		return strlen($password) >= 6;
	}

/**
| -------------------------------------------------------------------------
|  Phone
| -------------------------------------------------------------------------
|
| Checks that a phone number has 10 digits and format is properly
|
| @param string phone number
|
*/

 	function phone($phone)
	{
		$phone = preg_replace( '/\D/', '', $phone);

		if(strlen($phone) == 10)
		{
			return '('.substr($phone, 0, 3).') '.substr($phone, 3, 3).'-'.substr($phone, 6, 4);
		}

		return false;
	}

/**
| -------------------------------------------------------------------------
|  Old Password - Helper Function
| -------------------------------------------------------------------------
|
| Checks that password entered is valid for the current user
|
| @param string old_password
|
*/

 	function old_password($password)
	{
		$ci =& get_instance();

		//avoid name conflict error between model & library
		$ci->load->model('session', 'session_model');

		return $ci->session_model->password($password);
	}

	function is_unique($str, $field)
	{
		//post turns a period in a field name to an underscore.  to check we need to replicate
		return ($str == get_instance()->input->post(str_replace('.', '_', $field))) ?: parent::is_unique($str, $field);
	}

	// callback function to process the file upload and validate it
	function upload($field, $allowed, $max_size = 0)
	{
		//debug(func_get_args());
		//debug($_FILES);
		//debug($_POST);
		//die();

		$ci =& get_instance();

		$ci->load->library('upload');

		$ci->upload->initialize(
		[
			'upload_path'   => '../data/upload/',
			'file_name'		 => $field,
			'allowed_types' => str_replace([',', ' '], ['|', ''],  $allowed),
			'overwrite' 	 => TRUE,
			'max_size'		 => $max_size
		]);

		if (empty($_FILES[$field]['size']))
		{
			$this->set_message('upload', "Error uploading your file.  Please try again.");

			if (empty($this->_field_data[$field])) //if run twice then $field might have changed to full path and this index won't exist
			 	return true;

			return strpos($this->_field_data[$field]['rules'], 'required') === false;
		}

		if ($ci->upload->do_upload($field))
		{
			//Cleanup POST which has "file" keys just to trigger this callback
			$_POST = array_diff_key($_POST, $_FILES);

			//Give us access to the uploaded file
			$_POST['orig_filename'] =  $ci->upload->data()['client_name'];
			return $_POST['upload'] = $ci->upload->data()['full_path'];
		}

		// default upload filetype error is too generic to be helpful so we set a custom one
		if ($ci->upload->validate_upload_path() and ! $ci->upload->is_allowed_filetype())
		{
			$this->set_message('upload', "Only $allowed file types are allowed");
		}
		else
		{
			$this->set_message('upload', $ci->upload->display_errors());
		}

		return false;
	}

	// callback function to process the file upload and validate it
	function image($field, $type)
	{
		if ( ! $path = self::upload($field, 'png, jpg, jpeg, gif, bmp', 2048))
		{
			$this->set_message('image', $this->_error_messages['upload']);

			return false;
		}

		$ci =& get_instance();

		$ci->load->library('Image_lib', ['source_image' => $path, 'new_type' => $type, 'width' => 350, 'height' => 350]);

		$ci->image_lib->resize();
	}

	function approved($donee_id)
	{
		$this->set_message('approved', 'donee has not approved this donor');

		//No validation for donee boxes at the moment.  Maybe donor == ADMIN?
		if ($_POST['label_type'] == 'label only')
		{
			return true;
		}

		$approved= org::find($donee_id)->approved;

		return strpos($approved, ';'.$_POST['donor_id'].';') !== false;
	}

	function less_than($str, $max)
	{
		$ci =& get_instance();

		$max = is_numeric($max) ? $max : data::post($max);

		$max = is_array($max) ? $max[0] : $max;

		return parent::less_than($str, $max);
	}

	/**
	 * Radio
	 *
	 * Change required to only require one reponse per array
	 * rather than requiring every element to be selected
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */

	function radio($str, $field)
	{
		if (empty($this->radio[$field]))
		{
			$this->radio[$field] = (isset($_POST[$field]) and is_array($_POST[$field]) and array_filter($_POST[$field]));
		}

		return $this->radio[$field];
	}

	/**
	 * Alpha Space
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function alpha_space($str)
	{
		if ($str == '')
		{
			return TRUE;
		}

		return parent::alpha(str_replace(' ', '', $str));
	}

}

/* End of file MY_form_validation.php */
