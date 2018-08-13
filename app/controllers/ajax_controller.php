<?php
class Ajax_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Remote
| -------------------------------------------------------------------------
|
| Give backend codeigniter validation rules to jquery ajax validation
|
|
*/
	function remote()
	{
		$this->output->enable_profiler(FALSE);
		// Set JSON headers, no cache
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		$this->load->library('form_validation');

		//For JSON response, we don't want error delimiters
		$this->form_validation->set_error_delimiters('', '');

		$form = $_POST['form'];

		unset($_POST['form']);

		$this->form_validation->run($form);

		if ($error = form_error(key($_POST)))
		{
			log::error("post: ".http_build_query($_POST).", form: $form, error: $error");
		}

		$this->output->set_output(json_encode($error ?: true));
	}

	function beacon()
	{
		$load = $this->input->get('rload');

		if ($load > 7000)
		{
			$url = $this->input->get('url');

			log::info("Loading $url took {$load}ms");
		}
	}

	function autocomplete()
	{
		$this->output->enable_profiler(FALSE);
		header('Content-type: application/json');

		$value = 'CONCAT_WS(" ", name, "/", description, "/ NDC", upc)';

		$suggestions = $this->db
								  ->select("$value as value, id as data", false)
								  ->like($value, item::universal($_GET['query']))
								  ->get('item')->result_array();

								  //echo $this->db->last_query();
		$this->output->set_output(json_encode(['suggestions' => $suggestions]));
	}

	function registry($id = '', $qty = '')
	{
		if ($id !== '' && $qty !== '')
		{
			$this->db->query("INSERT INTO registry (id, qty) VALUES($id, $qty-1) ON DUPLICATE KEY UPDATE qty=$qty-1");

			redirect('http://www.sirum.org/registry');
		}
		else
		{
			$this->output->enable_profiler(FALSE);
			$ids = $this->db->get('registry')->result_array();

			header('Access-Control-Allow-Origin: http://www.sirum.org');
			header('Content-type: application/json');
			$this->output->set_output(json_encode($ids));
		}
	}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/