<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Controller extends the built-in CI Controller
 *
 *
 */
class MY_Controller extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->output->enable_profiler
		(
			data::get('admin') ?: $this->config->item('profiler')
		);
	}
}