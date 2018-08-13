<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class file
{
/**
| ---------------------------------------------------------
| Exists: does a file with given path exist
| ---------------------------------------------------------
| @param $path, full path or shortcut defined in file::path
| @param $file, must be supplied if shortcut given as path
| @param, $default, path to be given if file does not exist
| @return if $default: $path/$default, else true/false
*/
	function exists($path, $file = '', $default = '')
	{
		$this->load->helper('file');

		$exists = file_exists(self::path($path, $file));

		if ($default)
		{
			return $exists ? $file : $default;
		}

		return $exists;
	}

/**
| ---------------------------------------------------------
| Delete: deletes file
| ---------------------------------------------------------
| @param $path, full path or shortcut defined in file::path
| @param $file, must be supplied if shortcut given as path
| @return null
*/
	function delete($path, $file = '')
	{
		if (self::exists($path, $file))
		{
			unlink(self::path($path, $file));
		}
	}

/**
| ---------------------------------------------------------
| Read: returns file as string
| ---------------------------------------------------------
| @param $path, full path or shortcut defined in file::path
| @param $file, must be supplied if shortcut given as path
| @return string
*/
	function read($path, $file = '')
	{
		$this->load->helper('file');

		return read_file(self::path($path, $file));
	}

/**
| ---------------------------------------------------------
| Write: create file from string
| ---------------------------------------------------------
| @param $path, full path or shortcut defined in file::path
| @param $file, must be supplied if shortcut given as path
| @param $data, data string to write to file defaults to $_FILE
| @param $mode, optional constant defined in constants.php
| @return bool
*/
	function write($path, $file, $data, $mode = FOPEN_WRITE_CREATE_DESTRUCTIVE)
	{
		$this->load->helper('file');

		return write_file(self::path($path, $file), $data, $mode);
	}

	function download_csv($name, $rows)
	{
		//print_r($name);
		//print_r($rows);

		header("Content-type: application/csv");
	 	header("Content-Disposition: attachment; filename=\"$name".".csv\"");
	 	header("Pragma: no-cache");
	 	header("Expires: 0");

		$handle = fopen('php://output', 'w');

		foreach ($rows as $row) {
			 fputcsv($handle, $row);
		}

		fclose($handle);
	}

/**
| ---------------------------------------------------------
| Download: downloads file in browser
| ---------------------------------------------------------
| @param $path, full path or shortcut defined in file::path
| @param $file, must be supplied if shortcut given as path
| @return a downloaded file
*/
	function download($path, $file = '')
	{
		$this->load->helper('download');

		$this->output->enable_profiler(false);

		force_download($file, file_get_contents(self::path($path, $file)));
	}

/**
| ---------------------------------------------------------
| Load: formats file as an html string
| ---------------------------------------------------------
| @param $path, full path or shortcut defined in file::path
| @param $file, must be supplied if shortcut given as path
| @return string formatted as html
*/
	function load($path, $file = '')
	{
		$this->load->helper('typography');

		return auto_typography($this->load->file(self::path($path, $file), true));
	}

/**
| ---------------------------------------------------------
| Path: provides relative paths of defined folders
| ---------------------------------------------------------
| @param $path, full path or shortcut defined in switch
| @param $file, file name is appended to path if given
| @return relative path to folder, error if shortcut not found
*/
	function path($path, $file = '')
	{
		switch($path)
		{
			//Public folders with url paths
			case 'css':
			case 'js':
				$path = base_url().$path;
				break;

			//Public folders with relative paths
			case 'images':
			case 'doc':
			case 'label':
			case 'manifest':
			case 'edi':
				break;

			//Non-public data in date folder located above root
			case 'upload':
			case 'template':
				$path =  "../data/$path";
				break;

			//Non-public code located above root
			case 'log':
			case 'data':
				$path =  "../$path";
				break;

			//No matching folder was found
			default:
				if ( ! text::has($path, '/'))
				{
					trigger_error("Unknown File Path $path ".backtrace(1), E_USER_ERROR);
				}
				break;
		}

		return $file ? (text::has($file, 'http://') ? $file : "$path/$file") : $path;
	}



} //END OF CLASS

// ------------------------------------------------------------------------
