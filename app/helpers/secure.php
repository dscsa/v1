<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class secure
{

/**
| ---------------------------------------------------------
| Key: get specified password from a secure key file
| ---------------------------------------------------------
| @param $key, key of the password you wich to retrieve
| @note for extra security, encryption key is never returned
| @return string
*/
	function key($key)
	{
		$key = strtoupper($key);

		if ($key != 'ENCRYPT')
		{
			include_once('../key/'.ENVIRONMENT.'.php');

			return constant($key);
		}

		die('We never return the encryption key!');
	}

/**
| ---------------------------------------------------------
| Hash: secure one-way hash of a string, salt optional
| ---------------------------------------------------------
| @param $value, string to be securely hashed
| @param $default, default salt to be used. if blank one is created
| @return if default hash string, else array with salt & password keys
*/
	function hash($value, $default = '')
	{
		$this->load->library('encrypt');

		$this->load->helper('string');

		$salt = $default ?: md5(random_string('alnum', 40));

		include_once('../key/'.ENVIRONMENT.'.php');

		$password = $this->encrypt->hash(strrev($salt.md5($value)).$salt.md5(constant('ENCRYPT')));

		return $default ? $password : compact('salt', 'password');
	}

/**
| ---------------------------------------------------------
| Encrypt: encrypt a plain-text string
| ---------------------------------------------------------
| @param $value, string to be encrypted
| @return encrypted string
*/
	function encrypt($string)
	{
		$this->load->library('encrypt');

		return $this->encrypt->encode($string);
	}

/**
| ---------------------------------------------------------
| Decrypt: decrypt an encrypted string
| ---------------------------------------------------------
| @param $value, string to be decrypted
| @return decrypted string
*/
	function decrypt($string)
	{
		$this->load->library('encrypt');

		return $this->encrypt->decode($string);
	}

/**
| ---------------------------------------------------------
| Password: random alpha-numeric string of specified length
| ---------------------------------------------------------
| @param $length, number of characters in string
| @return random alpha-numeric string of specified length
*/
	function password($length = 8)
	{
		$this->load->helper('string');

		$text = random_string('alnum', 8);

		$hash = self::hash($text);

		return compact('text', 'hash');
	}


} //END OF CLASS AND FILE