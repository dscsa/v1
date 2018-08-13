<?php
class Formulary_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Index
| -------------------------------------------------------------------------
|
| Function displays medicine and supplies requests and allows user
| to modify or delete.  Accessible from modify on the top navigation bar.
| If user does not have permission to access page with transfer them to
| display inventory instead.
|
|
*/

	function index()
	{
		user::login($org_id, 'donee');

		if(data::post('delete'))
		{
			request::delete(key(data::post('delete')));

			$v['message'] = html::info(text::get('to_default', ['request', 'deleted from formulary']));
		}

		if(is_array(data::post('upc')))
		{
			request::create
			(
				$org_id,
				data::post('quantity', 0),
				data::post('upc'),
				data::post('type')
			);

			to::info(['Formulary', 'updated'], 'to_default', 'formulary');
		}

		$query = result::fields
		(
			['Name', 'item_group'],
			['subrow', '', 'requests()']
		);

		$this->db->order_by($this->input->get('o') ?: 'item_group',  $this->input->get('d') ?: 'asc');

		$v['pills'] = request::search($query + ['org_id' => $org_id], 'item_group');

		view::full('formulary', 'current formulary', $v);
	}

	function published()
	{

	}
}

/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
