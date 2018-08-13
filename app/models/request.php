<?php
class request extends MY_Model
{

/**
| -------------------------------------------------------------------------
|  Select
| -------------------------------------------------------------------------
|
|
*/
	function select()
	{
		return array
		(
			//sometime a request as 0 items so the join won't provide a req_id, so must use formulary_id
			'request'		=> ['*'],
			'item' 			=> ['id as item_id, SUBSTRING_INDEX( item.name, ",", 1) as item_group, name as item_name, description as item_desc, mfg, upc, price', 'id = request.item_id']
		);
	}

/**
| -------------------------------------------------------------------------
|  Create
| -------------------------------------------------------------------------
|
| In most cases we want to bulk add many requests (NDCs) to the formulary at a time
| so rather than have controller handle this, we let the model do the work of bulk adds.
| We also don't want to request the same NDC multiple times.
|
*/

	function create($org_id, $qty, $item_ids)
	{
		foreach ($item_ids as $item_id)
		{
			$where = ['item_id' => $item_id, 'org_id' => $org_id];

			$this->db->where($where);

			//Ensure if $qty < 0 that total does not become negative
			$this->db->set('original', "`original` + GREATEST($qty, -`original`)", false);
			$this->db->set('quantity', "`quantity` + GREATEST($qty, -`quantity`)", false);

			$this->db->update('request', ['updated'   => gmdate('c')]);

			//If not in donee inventory, add it now
			if ($this->db->affected_rows() == 0)
			{
				self::_create($where + ['original' => $qty, 'quantity' => $qty]);
			}
		}
	}

/**
| -------------------------------------------------------------------------
|  Delete
| -------------------------------------------------------------------------
|
| Delete request and all items in it
|
*/
	function delete($req_id)
	{
		self::_delete($req_id);
	}

}  // END OF CLASS
