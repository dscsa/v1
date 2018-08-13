<?php

class Record_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Donated
| -------------------------------------------------------------------------
|
| Display donation record.  Complement of received record
|
| @pram string print, options
|
*/

	function donated()
	{
		user::login($org_id);

		$query = result::fields
		(
			['Name', 'item_name', 'item_pop()', 'input()'],
			['Partner', 'partner', 'partner()', 'input()'],
			['Status', 'date_status', 'status()'],
			['', '', '', ['status' => 'dropdown()']],
			['Donor', 'donor_qty', 'donor', 'input()'],
			['Donee', 'donee_qty', 'donee', 'input()'],
			['', '', '', 'submit(Search)'],
			['',  '', 'button(donations/{donation_id}, Details)']
		);

		//Search was taking too long (and db running out of memory) without a specific search term
		$terms = count(array_filter($query));

		if($terms) {
			//item_id > 0 makes query significantly faster
			$query += ['item_id >' => 0, 'donor_id' => $org_id];

			//Added because otherwise mysql temp file size throws error, although this does ruin user sorting
			//$this->db->order_by('donation_items.id', 'desc');
			$v = ['record' => donation::search($query, 'donation_items.id')];
		} else {
			$v = ['record' => new result];
		}

		view::full('records', 'donated record', $v);
	}

	/**
	| -------------------------------------------------------------------------
	|  Received
	| -------------------------------------------------------------------------
	|
	| Display donation record.  Complement of received record
	|
	| @pram string print, options
	|
	*/

		function received()
		{
			user::login($org_id);

			$query = result::fields
			(
				['Name', 'item_name', 'item_pop()', 'input()'],
				['Partner', 'partner', 'partner()', 'input()'],
				['Status', 'date_status', 'status()'],
				['', '', '', ['status' => 'dropdown()']],
				['Donor', 'donor_qty', 'donor', 'input()'],
				['Donee', 'donee_qty', 'donee', 'input()'],
				['', '', '', 'submit(Search)'],
				['',  '', 'button(donations/{donation_id}, Details)']
			);

			//Search was taking too long (and db running out of memory) without a specific search term
			$terms = count(array_filter($query));

			if($terms) {
				//item_id > 0 makes query significantly faster
				$query += ['item_id >' => 0, 'donee_id' => $org_id, 'date_shipped >' => 0, 'date_verified IS NULL' => NULL];

				//Added because otherwise mysql temp file size throws error, although this does ruin user sorting
				//$this->db->order_by('donation_items.id', 'desc');
				$v = ['record' => donation::search($query, 'donation_items.id')];
			} else {
				$v = ['record' => new result];
			}

			view::full('records', 'received record', $v);
		}

		/**
		| -------------------------------------------------------------------------
		|  Verified
		| -------------------------------------------------------------------------
		|
		| Display donation record.  Complement of received record
		|
		| @pram string print, options
		|
		*/

			function verified()
			{
				user::login($org_id);

				$query = result::fields
				(
					['Name', 'item_name', 'item_pop()', 'input()'],
					['Partner', 'partner', 'partner()', 'input()'],
					['Status', 'date_status', 'status()'],
					['', '', '', ['status' => 'dropdown()']],
					['Donor', 'donor_qty', 'donor', 'input()'],
					['Donee', 'donee_qty', 'donee', 'input()'],
					['', '', '', 'submit(Search)'],
					['',  '', 'button(donations/{donation_id}, Details)']
				);

				//Search was taking too long (and db running out of memory) without a specific search term
				$terms = count(array_filter($query));

				if($terms) {
					//item_id > 0 makes query significantly faster
					$query += ['item_id >' => 0, 'donee_id' => $org_id, 'date_verified >' => 0];

					//Added because otherwise mysql temp file size throws error, although this does ruin user sorting
					//$this->db->order_by('donation_items.id', 'desc');
					$v = ['record' => donation::search($query, 'donation_items.id')];
				} else {
					$v = ['record' => new result];
				}

				view::full('records', 'verified record', $v);
			}


/**
| -------------------------------------------------------------------------
|  Destroyed
| -------------------------------------------------------------------------
|
| Display disposition record and shipping credit history
|
| @pram string print, options
|
*/

	function destroyed()
	{
		user::login($org_id);

		$query = result::fields
		(
			['Item Name', 'item_name', 'item_pop()', 'input()'],
			['Description', 'item_desc', '', 'input()'],
			['', '', '', 'submit(Search)'],
			['Qty', 'quantity'],
			['Date', 'archived', 'date(archived)', ['After' => 'input(date)', 'Before' => 'input(date)']]
		);
		//Added because otherwise mysql temp file size throws error, although this does ruin user sorting
		$this->db->order_by('donation_items.id', 'desc');

		$query += ['donee_id' => $org_id, 'donation_items.archived >' => 0, 'date_verified >' => 0, 'donee_qty >' => 0];

		$v = ['record' => donation::search($query, 'donation_items.id')];

		view::full('records', 'destroyed record', $v);
	}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
