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
	 'error_rows' => [],
	 'alerts' => [],
	 'pharmericaMonth' => '',
	 'shippedHolder' => '',
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
			$data[] = "ERROR";
			self::$bulk['error_rows'][] = $data;
			return;
		}

		if ( ! preg_match('/^[0-9-]+$/', $ndc))
		{
			self::$bulk['alerts'][] = "Row $row: NDC $ndc must be a number";
			$data[] = "Row $row: NDC $ndc must be a number";
			self::$bulk['error_rows'][] = $data;
			return;
		}

		if ($qty AND ! preg_match('/^[0-9.-]+$/', $qty))
		{
			self::$bulk['alerts'][] = "Row $row: Quantity $qty must be a number";
			$data[] = "Row $row: Quantity $qty must be a number";
			self::$bulk['error_rows'][] = $data;
			return;
		}

		if ($exp AND ! strtotime($exp))
		{
			self::$bulk['alerts'][] = "Row $row: Expiration $qty must be empty or a date";
			$data[] = "Row $row: Expiration $qty must be empty or a date";
			self::$bulk['error_rows'][] = $data;
			return;
		}

		if ($archived AND ! strtotime($archived))
		{
			self::$bulk['alerts'][] = "Row $row: Archived $qty must be empty or a date";
			$data[] = "Row $row: Archived $qty must be empty or a date";
			self::$bulk['error_rows'][] = $data;
			return;
		}

		//CSV's tend to chop off leading 0s this is meant to correct for that chop
		if (strpos($ndc, "-") === false)
			$ndc = str_pad($ndc, 9, "0", STR_PAD_LEFT);

		$items = item::search(['upc' => $ndc]);

		if (count($items) == 0)
		{
			self::$bulk['alerts'][] = "Row $row: $ndc was not found";
			$data[] = "Row $row: $ndc was not found";
			self::$bulk['error_rows'][] = $data;
			return;
		}

		if (count($items) > 1)
		{
			$results = [];
			foreach($items as $item) {
				$results[] = $item->upc;
			}
			self::$bulk['alerts'][] = "Row $row: $ndc had multiple results: ".join(", ", $results);
			$data[] = "Row $row: $ndc had multiple results: ".join(", ", $results);
			self::$bulk['error_rows'][] = $data;
			return;
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

			if ($value == 'drug._id') //V2
				self::$bulk['ndc'] = $index;

			if ($value == 'NDC') //Pharmerica
				self::$bulk['ndc'] = $index;

			if ($value == 'ndc') //Pharmerica
				self::$bulk['ndc'] = $index;

			if ($value == 'qty.to') //V2
				self::$bulk['qty'] = $index;

			if ($value == 'Return Quantity') //Pharmerica
				self::$bulk['qty'] = $index;

			if ($value == 'exp.to')
				self::$bulk['exp'] = $index;

			if ($value == 'verifiedAt')
				self::$bulk['verified'] = $index;

			if ($value == 'shipment._id')
				self::$bulk['donation_id'] = $index;

			if ($value == 'drug.generic') //V2
				self::$bulk['name'] = $index;

			if ($value == 'Drug Name') //Pharmerica
				self::$bulk['name'] = $index;

			if (strtolower($value) == 'drug label name') //Polaris
				self::$bulk['name'] = $index;

			if ($value == 'drug.brand')
				self::$bulk['description'] = $index;

			if ($value == 'drug.price.goodrx')
				self::$bulk['goodrx'] = $index;

			if ($value == 'drug.price.nadac')
				self::$bulk['nadac'] = $index;

			if ($value == 'drug.price.updatedAt')
 				self::$bulk['price_date'] = $index;

 			if ($value == 'shipment.tracking')
 				self::$bulk['tracking_num'] = $index;

 			if ($value == 'Pharmacy Name') //Pharmerica
 				self::$bulk['pharmacy_name'] = $index;
		}

		if (empty($data[self::$bulk['donation_id']])) {
			echo 'bulk';
			print_r(self::$bulk);
			echo 'data';
			print_r($data);
		}

	}


	function translate_num_to_month($raw){
		if($raw == '01'){
			return 'January';
		} else if($raw == '02'){
			return 'February';
		} else if($raw == '03'){
			return 'March';
		} else if($raw == '04'){
			return 'April';
		} else if($raw == '05'){
			return 'May';
		} else if($raw == '06'){
			return 'June';
		} else if($raw == '07'){
			return 'July';
		} else if($raw == '08'){
			return 'August';
		} else if($raw == '09'){
			return 'September';
		} else if($raw == '10'){
			return 'October';
		} else if($raw == '11'){
			return 'November';
		} else if($raw == '12'){
			return 'December';
		} 
	}

	//This is called from the inventory page only, at this point
	//An associative array of fields, and the row#
	function import($data, $row)
	{
		//$sum =& self::$bulk['sum'];
		$filename = data::post('orig_filename'); //gets the filename

    	//if ( ! $row % 100)
		//Unlike bulk() don't assume a certain field order.  Look for the correct names.
		//TODO if all required fields are not present we should throw an error
		if ($row == 1) {//Column headings
			if($data[0] == 'Donations Report'){
				self::$bulk['pharmericaMonth'] = '_'; //a placeholder until we can put a date
				return self::$bulk['alerts'][] = $data; //just copy taht row into error csv so that we can reupload
			} else { //then it's not pharmerica, so it has no extra rows above the headers
				self::setFields($data);
				return self::$bulk['alerts'][] = array_merge($data, ['error']);
			}
	  	}

	  	if((strlen(self::$bulk['pharmericaMonth']) > 0) AND ($row <= 6)){ //if it's pharmerica, and one of the first 6 rows (boilerplate stuff)
	  		if($row == 2){
	  			$raw_date = $data[0];
	  			preg_match('/ ([0-9]{2})\//',$raw_date,$m);
	  			$raw_month = substr($m[0], 1, 2);
	  			$word_month = self::translate_num_to_month($raw_month);
	  			$year = date("Y");
	  			self::$bulk['pharmericaMonth'] =  $word_month.'_'.$year;
	  			self::$bulk['shippedHolder'] = date::format($raw_month.'/01/'.$year, DB_DATE_FORMAT);
	  		} else if($row == 6){
	  			self::setFields($data);
	  			return self::$bulk['alerts'][] = array_merge($data, ['error']);
	  		}
	  		
	  		return self::$bulk['alerts'][] = $data; //copy in the first 5 rows so you can reupload with same code
	  	}

	  	//Initialize these to empty here because they all only get filled for V2 data
	  	$donee_phone = '';
	  	$date_verified = '';
	  	$donor_phone = '';
	  	$exp = '';
	  	$archived = '';
	  	$description = '';
	  	$price_date = '';
	  	$goodrx = '';
	  	$nadac = '';
	  	$price = '';
	  	$price_type = '';

	  	//this will be filled either by a column (in V2 data) or by filename (Coleman & Polaris). 
	  	//it will not be used for Pharmerica
	  	$tracking_num = '';

		//v2 shipment._id is in <10 digit recipient phone>.JSON Date.<10 digit donor phone>
		//v1 only accepts 11 character int for donation_id.

		if(array_key_exists('donation_id', self::$bulk)){ //IF A v2 CSV
			$donation_id = explode('.', $data[self::$bulk['donation_id']]);
			if (count($donation_id) == 3) {
			  list($donee_phone, $date_verified, $donor_phone) = $donation_id;
			} else if (count($donation_id) == 1) {
				return;  //skip repackaged items without an error (shipment.id = recipient phone)
			} else {
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: could not parse shipment._id.  It should have 0 or 2 periods"]);
			}

			//Format Date appropriately
			$date_verified = date::format($date_verified, DB_DATE_FORMAT);

			//Format Phone numbers appropriately
			$donor_phone = '('.substr($donor_phone, 0, 3).') '.substr($donor_phone, 3, 3).'-'.substr($donor_phone, 6, 4);
			$donee_phone = '('.substr($donee_phone, 0, 3).') '.substr($donee_phone, 3, 3).'-'.substr($donee_phone, 6, 4);
			
			//extract data in variables	
			$exp = $data[self::$bulk['exp']];
			$archived = $data[self::$bulk['verified']] ? '' : gmdate('c');
			$description = $data[self::$bulk['description']];
			$price_date = $data[self::$bulk['price_date']];
			$goodrx = $data[self::$bulk['goodrx']];
			$nadac = $data[self::$bulk['nadac']];
			$price = $goodrx ?: $nadac;
			$price_type = $goodrx ? 'goodrx' : 'nadac';
			$tracking_num = $data[self::$bulk['tracking_num']];
		}

		//Extract these 3 out here so we do it for non-V2 csv's as well
		$ndc = trim(str_replace("'0", "0", $data[self::$bulk['ndc']]));
		$qty = $data[self::$bulk['qty']];
		$name = $data[self::$bulk['name']];



		//if there was no tracking number in the columns then its not v2, and if they're not pharmerica
		//then we need a tracking number in file name, else the whole thign won't work
		if((!array_key_exists('tracking_num', self::$bulk)) AND ((strlen(self::$bulk['pharmericaMonth']) == 0))){
			preg_match('/([0-9]{12})/',$filename,$m);
			if(count($m) == 0){
				//error
				return self::$bulk['alerts'][] = array_merge($data, ["Filename must have actual SIRUM tracking number (12 digits) or needs a column titled 'tracking_num'"]);
			} else {
				$tracking_num = $m[0];
			}
		}

		$donor_id = $donee_id = ''; //only using these if pharmerica

		//for pharmerica, on each row, need to get donor and donee ids
		if((strlen(self::$bulk['pharmericaMonth']) > 0)){
			//month is self::$bulk['pharmericaMonth'] name is self::$bulk['pharmacy_name']
			//use name to find the donor id
			//get the latest donation with that donor id, take the donee id
			$full_name = "Pharmerica ".$data[self::$bulk['pharmacy_name']];
			$donor_obj = org::search(['org.name' => $full_name]);
			if(count($donor_obj) == 0){
				//weren't able to find pharmacy name
				return self::$bulk['alerts'][] = array_merge($data, ["Couldn't find Pharmacy with that $full_name . Might be under slightly differnt sirum.org name"]);	
			} else {
				//return self::$bulk['alerts'][] = array_merge($data, [$donor_obj[0]->id]);	
				$donor_id = $donor_obj[0]->id;
				$donations_obj = donation::search(['donor_id' => $donor_id]);
				$donee_id = $donations_obj[0]->donee_id;
			}
		}


		//Use regular expressions for validation.  We are going to have a download of all rows with Errors
		//so compine our uploaded data with the error if there is an issue and add it to the array of alerts.
		if ( ! preg_match('/^[0-9-]+$/', $ndc))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: NDC $ndc must be a number"]);
		}

		if ($qty AND ! preg_match('/^[0-9.]+$/', $qty))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: Quantity $qty must be a number"]);
		}

		if (strlen($exp) > 0 AND ! strtotime($exp))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: Expiration $exp must be empty or a date"]);
		}

		if (strlen($archived) > 0 AND ! strtotime($archived))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: Archived $archived must be empty or a date"]);
		}

		//Look up the uploaded NDC in our database.
		$items = item::search(['upc' => $ndc]);

		//If the NDC does not yet exist, try to create a new drug with it.
		if (count($items) == 0) //TODO MAKE THIS GENERALIZABLE
		{
			//We can only create a drug if we were provided a drug name.
			if (!$name)
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $ndc was not found and no name field was provided (column can be drug.generic, Drug Name, Drug Label Name", $filename]);

			$upc = '';

			$drug = (object) [ //these qualities, plus upc (see past next if/else) will always be added
				'updated'     => gmdate(DB_DATE_FORMAT),
				'type'			  => 'medicine',
				'name' 			  => $name,
				'description'	=> ($description ?: $name)." (Rx ".($description ? 'Brand' : 'Generic').")",
			];

			if(array_key_exists('donation_id', self::$bulk)){ //V2 is only one where ndc is dash-separated
				list($label, $prod) = explode('-', $ndc); //may need to pad the NDC with leading 0s into the 5-4 format so split it apart.
				$upc = str_pad($label, 5, '0', STR_PAD_LEFT).str_pad($prod, 4, '0', STR_PAD_LEFT);
				$drug->price = $price;
				$drug->price_date = $price_date;
				$drug->price_type = $price_type ? $price_type : '0000-00-00 00:00:00';
			} else {
				$upc =  substr($ndc, 0, 9); //TODO: Confirm that getting rid of package code like this is all we need to do
				$drug->price = 0;
				$drug->price_date = '0000-00-00 00:00:00';
				$drug->price_type = '';
			}

			$drug->upc = $upc;

			//Create the drug and store its id into an array
			$this->db->insert('item', $drug);
			$drug->id = $this->db->insert_id();
			$items[] = $drug;
		}

		//If the NDC has mutiple matches in our DB then something is wrong!
		if (count($items) > 1)
		{
			$results = [];
			foreach($items as $item) {
				$results[] = $item->upc;
			}
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $ndc had multiple results: ".join(", ", $results)]);
		}

		//Look up the uploaded donation/shipment in our DB
		//$donations = donation::search(['date_verified' => $date_verified]);
		$donations = [];
		if(strlen(self::$bulk['pharmericaMonth']) == 0){ //If not Pharmerica, then use tracking number
			$donations = donation::search(['tracking_number' => $tracking_num]);
		} else { //If pharmerica, lookup by dummy tracking number name
			//look up with pharmacy donor id and the placeholder name format ('Viewmaster_January_2018')
			$donations = donation::search(['donor_id' => $donor_id, 'tracking_number' => 'Viewmaster_'.self::$bulk['pharmericaMonth']]);
		}

		//If donation is not in the DB then try to create it for V2 or Pharmerica ONLY
		if (count($donations) == 0) {
			if((strlen(self::$bulk['pharmericaMonth']) > 0) OR (array_key_exists('donation_id', self::$bulk))){
				$donation = [];

				if(strlen(self::$bulk['pharmericaMonth']) > 0){ //Create fake Pharmerica
					$fake_tracking_number = 'Viewmaster_'.self::$bulk['pharmericaMonth']; //Viewmaster_January_2017
					//use $donor_id & $donee_id which come from first (or last?) donation
					//calculate date_shipped using pharmerica month
					//Add the donation and store its id in an array
					$donation = (object) [
						'date_shipped' => self::$bulk['shippedHolder'],
						'donor_id' => $donor_id,
						'donee_id' => $donee_id,
						'tracking_number' => $fake_tracking_number
					];

				} else { //Create corresponding V2 donation
					//Our shipment id had a unique identifier for donor/donee.  If we switch to tracking numbers the two orgs will need to be looked up in the DB
					//IF USING V2
					$donors = org::search(['phone' => $donor_phone]);
					$donees = org::search(['phone' => $donee_phone]);

					//If we can't find a donor then we can't add the donation/shipment.  Don't think we should automatically create an org
					if (count($donors) == 0)
						return self::$bulk['alerts'][] = array_merge($data, ["Row $row: donor phone $donor_phone did not have any matches"]);

					//If we can't find a donee then we can't add the donation/shipment. Don't think we should automatically create an org
					if (count($donees) == 0)
						return self::$bulk['alerts'][] = array_merge($data, ["Row $row: donee phone $donee_phone did not have any matches"]);
					//Add the donation and store its id in an array
					$donation = (object) [
						'date_shipped' => $date_verified,
						'date_verified' => $date_verified,
						'created'  => $date_verified,
						'donor_id' => $donors[0]->id,
						'donee_id' => $donees[0]->id
					];
				}

				//Add new donation to DB
				$this->db->insert('donation', $donation);
				$donation->donation_id = $this->db->insert_id();
				$donations[] = $donation;
			} else { //if not v2 or Pharmerica, we're not creating new shipments
				return self::$bulk['alerts'][] = array_merge($data, ["Tracking number $tracking_num does not match database, please correct"]);
			}
		}

		//Ok Item should have exactly one drug and one donation/shipment at this point so we should be able to add
		self::create([
			'donation_id'	=> $donations[0]->donation_id,
			'item_id'     => $items[0]->id,
			'donee_qty'		=> $qty,
			'org_id'      => $donations[0]->donee_id,
			'price' 	 		=> $items[0]->price ? $items[0]->price : 0,
			'price_date' 	=> $items[0]->price_date ? $items[0]->price_date : '0000-00-00 00:00:00',
			'price_type' 	=> $items[0]->price_type,
			'exp_date'		=> date::format($exp, DB_DATE_FORMAT),
			'archived'		=> date::format($archived, DB_DATE_FORMAT),
		]);

		//We use $archived to designate if the item was accept by the donee into inventory.  If it was accepted
		//then we need to increment our inventory of this drug by the qty we just added
		if ( ! $archived)
			self::increment(['org_id' => $donations[0]->donee_id, 'item_id' => $items[0]->id], $qty);
	}
}  // END OF CLASS
