<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class data extends MY_Helper
{

/**
| ---------------------------------------------------------
| Send: flash data retrievable after page load
| ---------------------------------------------------------
| @param $key, string used to retrieve val from data::sent
| @param $val, mixed to be retrieved from data::sent
| @return null
*/
	function send($key, $val)
	{
		$this->load->library('session');

		$this->session->set_flashdata($key, $val);
	}

/**
| ---------------------------------------------------------
| Sent: retrieve flash data passed by send
| ---------------------------------------------------------
| @param $key, string used as key in data::send
| @param $default, mixed return if key not found
| @return value set in data::send
*/
	function sent($key, $default = '')
	{
		$this->load->library('session');

		return $this->session->flashdata($key) ?: $default;
	}

/**
| ---------------------------------------------------------
| Get: session data stored by data::set
| ---------------------------------------------------------
| @param $key, string used as key in data::send
| @param $default, mixed return if key not found
| @return value set in data::set
*/
	function get($key = '', $default = '')
	{
		$this->load->library('session');

		return $key ? ($this->session->userdata($key) ?: $default) : $this->session->all_userdata();
	}

/**
| ---------------------------------------------------------
| Set: session data retrievable by data::get
| ---------------------------------------------------------
| @param $key, string or array [key1 => val1, key2 => val2]
| @param $val, mixed to be retrieved from data::get
| @return $key
*/
	function set($key = array(), $val = '')
	{
		$this->load->library('session');

		$this->session->set_userdata($key, $val);

		return $key;
	}

/**
| ---------------------------------------------------------
| Post: global post data
| ---------------------------------------------------------
| @param $key, form input's name, null returns entire array
| @param $default, mixed return if key not found in array
| @return value in the $_POST array
*/
	function post($key = null, $default = '')
	{
		return $this->input->post($key) ?: $default;
	}

/**
| ---------------------------------------------------------
| All: post or get data
| ---------------------------------------------------------
| @param $key, form input's name, null returns entire array
| @param $default, mixed return if key not found in array
| @return value in the $_POST array
*/
	function all($key = null, $default = '')
	{
		return $this->input->post($key) ?: $this->input->get($key) ?: $default;
	}

/**
| ---------------------------------------------------------
| Delete: delete data from data::post
| ---------------------------------------------------------
| @param $key, form input's name w/ [] if applicable
| @param $index, deletes entire key unless index specified
| @note retains information in global post array, $_POST
| @return null
*/
	function delete($key, $index = '')
	{
		if ($index !== '')
		{
			unset($this->form_validation->_field_data[$key]['postdata'][$index]);
		}
		else
		{
			unset($this->form_validation->_field_data[$key]['postdata']);
		}
	}

/**
| ---------------------------------------------------------
| Insert: prepend data to key's value in data::post
| ---------------------------------------------------------
| @param $key, form input's name w/ the [] if applicable
| @param $value, value to insert
| @note retains information in global post array, $_POST
| @return
*/
	function insert($key, $value = '')
	{
		//simplifies syntax and takes place of checking isset()
		$post = & $this->form_validation->_field_data[$key]['postdata'];

		is_array($post) ? array_unshift($post, $value) : $post = $value;
	}

} //END OF CLASS AND FILE