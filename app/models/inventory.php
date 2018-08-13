<?php
class inventory extends MY_Model
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
			'donation_items'		    => ['id as id, org_id as org_id, donee_qty, donor_qty, price, SUM(price * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty)) as value, COUNT(donation_items.id) as count, updated, created, archived'],
			'donation'				    => ['id as donation_id, GREATEST(date_shipped, date_received, date_verified) as date_status', 'id = donation_items.donation_id'],
			'item' 					    => ['id as item_id, name as item_name, description as item_desc, type, mfg, upc, price, price_date, price_type, image, imprint, color, shape, size', 'id = donation_items.item_id'], //
		);
	}

/**
| -------------------------------------------------------------------------
|  Make Where
| -------------------------------------------------------------------------
|
*/
	function _make_where($where)
	{
		if ( ! isset($where['donee_id']))
		{
			$this->db->where('donation_id', 0);
		}

		parent::_make_where($where);
	}

/**
| -------------------------------------------------------------------------
|  Where
| -------------------------------------------------------------------------
|
*/

	function where($field, $value)
	{
		switch($field)
		{
			case 'donor_id':

				$this->db
						->where('donation_items.org_id', $value)
						->where('donation_id', 0);
				break;

			case 'donee_id':

				$value = $value ? "AND request.org_id = $value" : "";

				$this->db
					->select('donation_items.org_id as donor_id, MAX(org.id) as donee_id, MAX(org.name) as donee_org, MAX(org.city) as donee_city, MAX(org.state) as donee_state, MAX(org.license) as donee_license, MAX(request.quantity) as requested, SUM(IF(request.quantity > 0 AND donor_id IS NULL AND org.id = request.org_id, 1, 0)) as matches,  count( DISTINCT IF(org.id = donation.donee_id, donation.id, null)) as donations')
					->from('org') //multitable from, because left joining orgs won't list orgs that don't have any matching requests
					->join('request', "request.item_id = donation_items.item_id $value", 'left')
					->where('org.approved LIKE CONCAT("%;", donation_items.org_id, ";%")')
					->where('org.date_donee >', 0);

				break;

			case 'description':
				$this->db->like('item.description',$value);
				break;

			//TODO this is duplicated from model::item
			case 'upc':
				item::universal($value);
				break;

			default:
				parent::where($field, $value);
				break;
		}
	}

	function increment($where, $qty)
	{
		$this->db->where($where + ['donation_id' => 0]);

		//Adding any number to NULL = NULL, so we need an IFNULL statement
		$this->db->set('donor_qty', "GREATEST($qty + IFNULL(`donor_qty`, 0), 0)", false);

		$this->db->update('donation_items', ['updated'   => gmdate('c')]);

		//If not in donee inventory, add it now
		if ($this->db->affected_rows() == 0)
		{
			inventory::create($where + ['donor_qty' => $qty]);
		}
	}

	static $bulk  =
	[
		'sum' => 0,
	 'row'    => 0,
	 'upload' => [],
	 'alerts' => []
	];

	function bulk($data)
	{
		list($ndc, $qty, $exp, $verified, $donation_id) = array_pad($data, 5, '');

		//Compatibility with website 2.0
		$archived = $verified ? '' : gmdate('c');

		//Trim doesn't work with &nbsp;
		$qty = str_replace('&nbsp;', '', htmlentities($qty));
		$ndc = trim($ndc);

		$row =& self::$bulk['row'];

		$row++;

		if ($row == 1) //Column headings
		{
			return;
		}

		if ( ! preg_match('/^[0-9-]+$/', $ndc))
		{
			return self::$bulk['alerts'][] = "Row $row: NDC $ndc must be a number";
		}

		if ($qty AND ! preg_match('/^[0-9.-]+$/', $qty))
		{
			return self::$bulk['alerts'][] = "Row $row: Quantity $qty must be a number";
		}

		if ($exp AND ! strtotime($exp))
		{
			return self::$bulk['alerts'][] = "Row $row: Expiration $qty must be empty or a date";
		}

		if ($archived AND ! strtotime($archived))
		{
			return self::$bulk['alerts'][] = "Row $row: Archived $qty must be empty or a date";
		}

		//CSV's tend to chop off leading 0s this is meant to correct for that chop
		if (strpos($ndc, "-") === false)
			$ndc = str_pad($ndc, 9, "0", STR_PAD_LEFT);

		$items = item::search(['upc' => $ndc]);

		if (count($items) == 0)
		{
			return self::$bulk['alerts'][] = "Row $row: $ndc was not found";
		}

		if (count($items) > 1)
		{
			$results = [];
			foreach($items as $item) {
				$results[] = $item->upc;
			}
			return self::$bulk['alerts'][] = "Row $row: $ndc had multiple results: ".join(", ", $results);
		}

		self::$bulk['upload'][] =
		[
			'donation_id' => $donation_id,
			'id'         => $items[0]->id,
			'price'			 => $items[0]->price,
			'price_date' => $items[0]->price_date,
			'price_type' => $items[0]->price_type,
			'ndc'        => $ndc,
			'exp_date'   => date::format($exp, DB_DATE_FORMAT),
			'archived'   => date::format($archived, DB_DATE_FORMAT),
			'dispensed'  => $qty,
			'verb'       => $qty > 0 ? 'increased' : 'decreased'
		];
	}

	function setFields($data) {
		foreach ($data as $index => $value) {

			if ($value == 'drug._id')
				self::$bulk['ndc'] = $index;

			if ($value == 'qty.to')
				self::$bulk['qty'] = $index;

			if ($value == 'exp.to')
				self::$bulk['exp'] = $index;

			if ($value == 'verifiedAt')
				self::$bulk['verified'] = $index;

			if ($value == 'shipment._id')
				self::$bulk['donation_id'] = $index;

			if ($value == 'drug.generic')
				self::$bulk['name'] = $index;

			if ($value == 'drug.brand')
				self::$bulk['description'] = $index;

			if ($value == 'drug.price.goodrx')
				self::$bulk['goodrx'] = $index;

			if ($value == 'drug.price.nadac')
				self::$bulk['nadac'] = $index;

			if ($value == 'drug.price.updatedAt')
 				self::$bulk['price_date'] = $index;
		}

		if (empty($data[self::$bulk['donation_id']])) {
			echo 'bulk';
			print_r(self::$bulk);
			echo 'data';
			print_r($data);
		}

	}

	function import($data, $row)
	{

		$sum =& self::$bulk['sum'];

    //if ( ! $row % 100)

		if ($row == 1) {//Column headings
			self::setFields($data);
			return self::$bulk['alerts'][] = array_merge($data, ['error']);
	  }

		//v2 shipment._id is in <10 digit recipient phone>.JSON Date.<10 digit donor phone>
		//v1 only accepts 11 character int for donation_id.
		$donation_id = explode('.', $data[self::$bulk['donation_id']]);
		if (count($donation_id) == 3) {
		  list($donee_phone, $date_verified, $donor_phone) = $donation_id;
		} else if (count($donation_id) == 1) {
			return;  //skip repackaged items without an error (shipment.id = recipient phone)
		} else {
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: could not parse shipment._id.  It should have 0 or 2 periods"]);
		}

		$date_verified = date::format($date_verified, DB_DATE_FORMAT);

		$donor_phone = '('.substr($donor_phone, 0, 3).') '.substr($donor_phone, 3, 3).'-'.substr($donor_phone, 6, 4);
		$donee_phone = '('.substr($donee_phone, 0, 3).') '.substr($donee_phone, 3, 3).'-'.substr($donee_phone, 6, 4);

		$ndc = str_replace("'0", "0", $data[self::$bulk['ndc']]);
		$qty = $data[self::$bulk['qty']];
		$exp = $data[self::$bulk['exp']];
		$archived = $data[self::$bulk['verified']] ? '' : gmdate('c');
		$name = $data[self::$bulk['name']];
		$description = $data[self::$bulk['description']];
		$price_date = $data[self::$bulk['price_date']];
		$goodrx = $data[self::$bulk['goodrx']];
		$nadac = $data[self::$bulk['nadac']];
		$price = $goodrx ?: $nadac;
		$price_type = $goodrx ? 'goodrx' : 'nadac';

		$sum += $qty;

		if ( ! preg_match('/^[0-9-]+$/', $ndc))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: NDC $ndc must be a number"]);
		}

		if ($qty AND ! preg_match('/^[0-9.]+$/', $qty))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: Quantity $qty must be a number"]);
		}

		if ($exp AND ! strtotime($exp))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: Expiration $exp must be empty or a date"]);
		}

		if ($archived AND ! strtotime($archived))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: Archived $archived must be empty or a date"]);
		}

		$items = item::search(['upc' => $ndc]);

		if (count($items) == 0)
		{
			if ( ! $name)
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $ndc was not found and no drug.generic field was provided"]);

			list($label, $prod) = explode('-', $ndc);

			$drug = (object) [
				'updated'     => gmdate(DB_DATE_FORMAT),
				'type'			  => 'medicine',
				'name' 			  => $name,
				'description'	=> ($description ?: $name)." (Rx ".($description ? 'Brand' : 'Generic').")",
				'upc' 			  => str_pad($label, 5, '0', STR_PAD_LEFT).str_pad($prod, 4, '0', STR_PAD_LEFT),
				'price'			  => $price,
				'price_date'  => $price_date,
				'price_type'  => $price_type,
			];

			$this->db->insert('item', $drug);
			$drug->id = $this->db->insert_id();
			$items[] = $drug;
		}

		if (count($items) > 1)
		{
			$results = [];
			foreach($items as $item) {
				$results[] = $item->upc;
			}
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $ndc had multiple results: ".join(", ", $results)]);
		}

		$donations = donation::search(['date_verified' => $date_verified]);

		if (count($donations) == 0) {
			$donors = org::search(['phone' => $donor_phone]);
			$donees = org::search(['phone' => $donee_phone]);

			if (count($donors) == 0)
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: donor phone $donor_phone did not have any matches"]);

			if (count($donees) == 0)
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: donee phone $donee_phone did not have any matches"]);

			$donation = (object) [
				'date_shipped' => $date_verified,
				'date_verified' => $date_verified,
				'created'  => $date_verified,
				'donor_id' => $donors[0]->id,
				'donee_id' => $donees[0]->id
			];

			$this->db->insert('donation', $donation);
			$donation->donation_id = $this->db->insert_id();
			$donations[] = $donation;
		}

		//echo "<br>row $row, qty $qty, sum $sum";

		self::create([
			'donation_id'	=> $donations[0]->donation_id,
			'item_id'     => $items[0]->id,
			'donee_qty'		=> $qty,
			'org_id'      => $donations[0]->donee_id,
			'price' 	 		=> $items[0]->price,
			'price_date' 	=> $items[0]->price_date,
			'price_type' 	=> $items[0]->price_type,
			'exp_date'		=> date::format($exp, DB_DATE_FORMAT),
			'archived'		=> date::format($archived, DB_DATE_FORMAT),
		]);

		if ( ! $archived)
			self::increment(['org_id' => $donations[0]->donee_id, 'item_id' => $items[0]->id], $qty);
	}
}  // END OF CLASS
