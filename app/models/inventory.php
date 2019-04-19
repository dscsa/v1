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
	 'quasi_cache' => [],
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

		log::info('inventory::setFields');

		foreach ($data as $index => $value) {

			if ($value == 'drug._id') //V2
				self::$bulk['ndc'] = $index;

			if ($value == 'NDC') //Pharmerica
				self::$bulk['ndc'] = $index;

			if ($value == 'ndc') //Pharmerica
				self::$bulk['ndc'] = $index;

			if ($value == 'qty.to') //V2
				self::$bulk['qty'] = $index;

			if ($value == 'Qty') //Coleman
				self::$bulk['qty'] = $index;

			//if(strpos(strtolower($value),'qty') !== false)
			//	self::$bulk['qty'] = $index;

			if ($value == 'Return Quantity') //Pharmerica
				self::$bulk['qty'] = $index;

			if ($value == 'Return Qty') //Polaris
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

			if ($value == 'Drug Name & Strength') //Coleman
				self::$bulk['name'] = $index;

			if (strtolower($value) == 'drug label name') //Polaris
				self::$bulk['name'] = $index;

			if ($value == 'drug.brand')
				self::$bulk['description'] = $index;

                        if ($value == 'description')
                                self::$bulk['description'] = $index;

			if ($value == 'drug.price.goodrx')
				self::$bulk['goodrx'] = $index;

			if ($value == 'drug.price.nadac')
				self::$bulk['nadac'] = $index;

			if ($value == 'drug.price.updatedAt')
 				self::$bulk['price_date'] = $index;

 			if ($value == 'shipment.tracking')
 				self::$bulk['tracking_num'] = $index;

 			 if ($value == 'tracking')
 				self::$bulk['tracking_num'] = $index;

 			 if ($value == 'tracking number')
 				self::$bulk['tracking_num'] = $index;

 			 if ($value == 'tracking num')
 				self::$bulk['tracking_num'] = $index;

			if($value == 'date_str')
				self::$bulk['date_str'] = $index;

 			if ($value == 'Pharmacy Name') //Pharmerica
 				self::$bulk['pharmacy_name'] = $index;

			if($value == 'pharmacy_name')
				self::$bulk['polaris_pharmacy_name'] = $index;

      			if($value == 'trusted_source')
        			self::$bulk['trusted_source'] = $index;

                        if($value == 'rx_otc')
                                self::$bulk['rx_otc'] = $index;

                        if($value == 'brand_generic')
                                self::$bulk['brand_generic'] = $index;

                        if($value == 'mfg')
                                self::$bulk['mfg'] = $index;

                        if($value == 'url')
                                self::$bulk['url'] = $index;

                        if($value == 'colorado_exact_ndc')
                                self::$bulk['colorado_exact_ndc'] = $index;

		}
		//print_r('bulk');
		//print_r(self::$bulk);
		/*if (empty($data[self::$bulk['donation_id']])) {
			echo 'bulk';
			print_r(self::$bulk);
			echo 'data';
			print_r($data);
		}*/

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



	function isPharmerica(){
		return strlen(self::$bulk['pharmericaMonth']) > 0;
	}

	function isV2(){
		return array_key_exists('donation_id', self::$bulk);
	}

	function isTrusted(){
                return array_key_exists('trusted_source', self::$bulk);
	}

	function isPolaris(){
		return array_key_exists('date_str', self::$bulk);
	}

	
	//Function that gets pinged by a GSheet WebApp, and given two urls
	//url_a will point to a source of some batches of data to import. We send a GET request to that link and parse the JSON there
	//url_b will be listening for a POST request with relavant results after we complete the import
	function autoUploadListener($url_a,$url_b){
		//Get the batch at $url_a
		$res = file_get_contents(base64_decode($url_a));
                $temp_json = json_decode($res,TRUE); 
		$batch_data = json_decode($temp_json['batch_data']);
		$batch_name = $temp_json['batch_name']; //keep track of this for the final ping to $url_b, so we can match errors appropriately
		self::autoUpload($batch_data, $batch_name, base64_decode($url_b));
		
	}
	

	//Handles taking the array that was sent from GSheets & actually calling self::import, then returns alerts to url_b so they can be addressed
	//Can't just return results to original API call because will timeout
	function autoUpload($arr, $batch_name, $res_ping_url){
		
		//Actually call import on the rows
		for($i = 0; $i < count($arr);$i++){
                	self::import($arr[$i], $i+1);
                }
		
		$res_ping_url = $res_ping_url."?batch_name=".urlencode($batch_name); //send this info so GSheet webapp can match errors to file
		$data = json_encode(self::$bulk['alerts']); //send any alert rows with errors

                $ch = curl_init($res_ping_url);
                curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

	}

	
	//This is called from the inventory page or Items page
	//Or through autoupload Gsheets integration
	//An associative array of fields, and the row#
	function import($data, $row)
	{
		//set_time_limit(5);

		log::info('inventory::import');

		if((self::isTrusted()) AND ($row > 1)){
			log::info('inventory::import isTrusted');
      			echo "TRUSTED";
			flush();
			//barebones code to handle periodically adding new ndcs or updating prices
			//the 'trusted_source' tag only gets used by OS or AK, with well-formatted data
			$ndc = trim(str_replace("'0", "0", $data[self::$bulk['ndc']]));
                      	$name = $data[self::$bulk['name']];
			$price_date = array_key_exists('price_date', self::$bulk) ? $data[self::$bulk['price_date']] : "";
                        $goodrx = array_key_exists('goodrx', self::$bulk) ? $data[self::$bulk['goodrx']] : "";
                        $nadac = array_key_exists('nadac', self::$bulk) ? $data[self::$bulk['nadac']] : "";
                        $price = $goodrx ?: $nadac;
                        $price_type = $goodrx ? 'goodrx' : 'nadac';
                        $description= array_key_exists('description', self::$bulk) ? $data[self::$bulk['description']] : "";

			$ndc = str_pad($ndc, 9, '0', STR_PAD_LEFT);
                        $items = item::search(['upc' => $ndc]);
                	if(count($items) == 1){//require exact match
				//then this NDC was found, and I want to update price
				$this->db->where('id', $items[0]->id);
				if($price) $this->db->set('price',$price);
				if($price_type) $this->db->set('price_type',$price_type);
				if($price_date) $this->db->set('price_date',$price_date);
				$this->db->update('item');
			} else if(count($items) == 0){
                		//then we want to add this ndc, with all relavant info
				$drug = (object) [ //these qualities, plus upc (see past next if/else) will always be added
                                'updated'     => gmdate(DB_DATE_FORMAT),
                                'type'                    => 'medicine',
                                'name'                    => $name,
                                'description'   => ($description ?: $name)." (Rx ".($description ? 'Brand' : 'Generic').")",
                        	];

				$drug->price = $price ? $price : 0;
	                        $drug->price_date = $price_date ? $price_date : '0000-00-00 00:00:00';
        	                $drug->price_type = $price_type? $price_type : '';
                	        $drug->upc = $ndc;

                        	$this->db->insert('item', $drug);

			}
			return;
		}

		if($row > 2500){
                       return self::$bulk['alerts'][] = array_merge($data, ['beyond row limit. just reupload the error csv']);
			}

		if($row % 50 == 0){
			//header("Refresh:0");
			//header("HTTP/1.0 102 Processing");
			log::info('inventory::import row updates');
			echo "Processing row: ".$row."<br>";
			//flush();
			print_r("...<br>");
			//ob_flush();
			//ob_start();
			//return self::$bulk['alerts'][] = array_merge($data, ['beyond row limit. just reupload the error csv']);
		}

		//if($row > 300){
		//	return self::$bulk['alerts'][] = array_merge($data, ['beyond row limit. just reupload the error csv']);
		//}
		//$sum =& self::$bulk['sum'];
		$filename = "";
		if(array_key_exists('orig_filename', self::$bulk['quasi_cache'])){
			$filename = self::$bulk['quasi_cache']['orig_filename'];
		} else {
			$filename = data::post('orig_filename');
			self::$bulk['quasi_cache']['orig_filename'] = $filename;
		}
		//$filename = data::post('orig_filename'); //gets the filename

    		//if ( ! $row % 100)
		//Unlike bulk() don't assume a certain field order.  Look for the correct names.
		//TODO if all required fields are not present we should throw an error
		if ($row == 1) {//Column headings
			//print_r("HERE");
			//print_r(implode("::",$data));
			if(strpos($data[0],'Donations Report') !== false){
				self::$bulk['pharmericaMonth'] = '_'; //a placeholder until we can put a date
				return self::$bulk['alerts'][] = $data; //just copy taht row into error csv so that we can reupload
			} else { //then it's not pharmerica, so it has no extra rows above the headers
				self::setFields($data);
				return self::$bulk['alerts'][] = array_merge($data, ['error']);
			}
	  	}

	  	if(self::isPharmerica() AND ($row <= 6)){ //if it's pharmerica, and one of the first 6 rows (boilerplate stuff)
	  		if($row == 2){
	  			$raw_date = $data[0];
	  			preg_match('/ ([0-9]{2})\//',$raw_date,$m);
	  			$raw_month = substr($m[0], 1, 2);
	  			$word_month = self::translate_num_to_month($raw_month);
	  			$year = date("Y");
	  			self::$bulk['pharmericaMonth'] =  $word_month.'_'.$year;
	  			self::$bulk['shippedHolder'] = date::format($raw_month.'/01/'.$year.' 10:00:00', DB_DATE_FORMAT);
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
		$rx_otc = '';
		$brand_generic = '';
		$mfg = '';
		$url = '';
		$colorado_exact_ndc = '';	

	  	//this will be filled either by a column (in V2 data) or by filename (Coleman & Polaris).
	  	//it will not be used for Pharmerica
	  	$tracking_num = '';

		//v2 shipment._id is in <10 digit recipient phone>.JSON Date.<10 digit donor phone>
		//v1 only accepts 11 character int for donation_id.

		//GET ALL THE ITEMS OUT OF THE ROW FIRST
		if(self::isV2()){ //IF A v2 CSV
			$donation_id = explode('.', $data[self::$bulk['donation_id']]);
			if (count($donation_id) ==3) {
			  	//$donor_phone = $donation_id[2]//explode('.',$donation_id[0])[0];
                                //$donee_phone = $donation_id[0]//$explode('.',$donation_id[1])[0];
                                //$date_verified = $donation_id[1] //explode('.',$donation_id[0])[1];
				list($donee_phone, $date_verified, $donor_phone) = $donation_id;
			} else if (count($donation_id) == 1) {
				return;  //skip repackaged items without an error (shipment.id = recipient phone)
			} else {
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: could not parse shipment._id.  It should have <donor_phone>.<date_ver>;<donee_phone>.<date_ver>"]);
			}
			//Format Date appropriately
			$date_verified = date::format($date_verified, DB_DATE_FORMAT);

			//Format Phone numbers appropriately
			$donor_phone = '('.substr($donor_phone, 0, 3).') '.substr($donor_phone, 3, 3).'-'.substr($donor_phone, 6, 4);
			$donee_phone = '('.substr($donee_phone, 0, 3).') '.substr($donee_phone, 3, 3).'-'.substr($donee_phone, 6, 4);

			//extract data in variables
			$exp = $data[self::$bulk['exp']];

			$archived = array_key_exists('verified', self::$bulk) ?  $data[self::$bulk['verified']] : "";
		}
	
		//Pull some data that may only be relavant if we need to add the drug	
		$description = array_key_exists('description', self::$bulk) ? $data[self::$bulk['description']] : "";
                $price_date = array_key_exists('price_date', self::$bulk) ? $data[self::$bulk['price_date']] : "";
                $goodrx = array_key_exists('goodrx', self::$bulk) ? $data[self::$bulk['goodrx']] : "";
                $nadac = array_key_exists('nadac', self::$bulk) ? $data[self::$bulk['nadac']] : "";
                $price = $goodrx ?: $nadac;
                $price_type = $goodrx ? 'goodrx' : 'nadac';
                $rx_otc = array_key_exists('rx_otc', self::$bulk) ? $data[self::$bulk['rx_otc']] : "";
                $brand_generic = array_key_exists('brand_generic', self::$bulk) ? $data[self::$bulk['brand_generic']] : "";
                $mfg = array_key_exists('mfg', self::$bulk) ? $data[self::$bulk['mfg']] : "";
                $url = array_key_exists('url', self::$bulk) ? $data[self::$bulk['url']] : "";
		$colorado_exact_ndc = array_key_exists('colorado_exact_ndc', self::$bulk) ? $data[self::$bulk['colorado_exact_ndc']] : "";


		
		$ndc = '';
		//Extract these 3 out here so we do it for non-V2 csv's as well
		if(array_key_exists('ndc', self::$bulk)){
			$ndc = trim(str_replace("'0", "0", $data[self::$bulk['ndc']]));
		}

		$qty = $data[self::$bulk['qty']];
		if(!$qty){
			return self::$bulk['alerts'][] = array_merge($data, ["Couldn't find a quantity. Make sure column is called qty.to, Return Quantity or Return Qty"]);
		}
		$name = '';
		if(array_key_exists('name', self::$bulk)){
			$name = $data[self::$bulk['name']];
		}
		//If there is a tracking number column, use it
		if(array_key_exists('tracking_num', self::$bulk)){
			$tracking_num = $data[self::$bulk['tracking_num']];
			//if(self::isV2()){
			//	$tracking_num = substr($tracking_num, 7); //because of how v2 adds seven extra digits to the front
			//}
		}

		//if there was no tracking number in the columns and if they're not pharmerica
		//then we need a tracking number in file name, else the whole thign won't work
		if((!array_key_exists('tracking_num', self::$bulk)) AND !self::isPharmerica()){
			preg_match('/([0-9]{15})/',$filename,$m);
			if(count($m) == 0){
				preg_match('/([0-9]{6})/',$filename,$m); //if coleman this will match
				if(count($m) == 0){
					return self::$bulk['alerts'][] = array_merge($data, ["Filename must have actual SIRUM tracking number (last 6 or full 15 digits) or needs a column titled 'tracking_num'"]);
				} else {
					$tracking_num = '971424215'.$m[0];
				}
			} else {
				$tracking_num = $m[0];
			}
		}



		$donor_id = $donee_id = ''; //only using these if pharmerica
		$full_name = '';
		$is_new_facility = False;
		//for pharmerica, on each row, need to get donor and donee ids
		if(self::isPharmerica()){
			//month is self::$bulk['pharmericaMonth'] name is self::$bulk['pharmacy_name']
			//use name to find the donor id
			//get the latest donation with that donor id, take the donee id
			//$full_name = '';

			//double check for weird pharmerica names that are different in their data vs theri viewmsater data
			if(strtolower($data[self::$bulk['pharmacy_name']]) == "colorado sprngs"){ //THIS IS A TYPEO IN THEIR VIEWMASTER DATA for some reason
				$full_name = "Pharmerica Colorado Springs";
			} else {
				$full_name = "Pharmerica ".$data[self::$bulk['pharmacy_name']]; //then they refer to themselves the same way
			}


			//get the donor & donee id's, either from DB or from cache
	                $donor_obj = [];
                	if(array_key_exists('full_name', self::$bulk['quasi_cache']) AND (self::$bulk['quasi_cache']['full_name'] == $full_name)){
				$donor_id = self::$bulk['quasi_cache']['donor_id'];
				$donee_id = self::$bulk['quasi_cache']['donee_id'];
                	} else {
				$is_new_facility = True;
                        	$donor_obj = org::search(['org.name' => $full_name]);
				if(count($donor_obj) == 0){
	                                //weren't able to find pharmacy name
        	                        return self::$bulk['alerts'][] = array_merge($data, ["Couldn't find Pharmacy with the name: $full_name . Might be under slightly differnt sirum.org name"]);
                	        } else {
                        	        //return self::$bulk['alerts'][] = array_merge($data, [$donor_obj[0]->id]);
                                	//self::$bulk['quasi_cache']['full_name'] = $donor_obj[0]->name; //change cached name
					$donor_id = $donor_obj[0]->id;
					//self::$bulk['quasi_cache']['donor_id'] = $donor_id;
                                	$donations_obj = donation::search(['donor_id' => $donor_id]);
                                	if(count($donations_obj) == 0){
                                        	return self::$bulk['alerts'][] = array_merge($data, ["Couldn't find any donations by $full_name that exist. Please create one in V1 (by making a new label) to their appropriate recipient."]);
                                	}	else {
                                        	$donee_id = $donations_obj[0]->donee_id;
                                	}
                       		 }
				self::$bulk['quasi_cache']['donee_id'] = $donee_id;
                                 self::$bulk['quasi_cache']['donor_id'] = $donor_id; //change cached donor id
				self::$bulk['quasi_cache']['full_name'] = $full_name; //$donor_obj[0]->name; //change cached name
                	}

		}


		//Use regular expressions for validation.
		//log::info('inventory::import preg_match ndc');
		if ((strlen($ndc) > 0) AND (strlen($colorado_exact_ndc) == 0) AND (! preg_match('/^[0-9-]+$/', $ndc)))
		{
			return self::$bulk['alerts'][] = array_merge($data, ["Row $row: NDC $ndc must be a number"]);
		}

		//log::info('inventory::import pre_match qty');
		if ($qty AND ! preg_match('/^-?[0-9.]+$/', $qty))
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

		$looked_up_by_name = false;
		//Look up the uploaded NDC in our database.
		$items = [];
		
		if(strlen($colorado_exact_ndc) > 0){
			//search using colorado ndc
			$ndc_res = explode('-', $colorado_exact_ndc); //may need to pad the NDC with leading 0s into the 5-4 format so split it apart.
                        if(count($ndc_res) == 3){
				$label = $ndc_res[0];
				$prod = $ndc_res[1];
				$ndc = str_pad($label, 5, '0', STR_PAD_LEFT).str_pad($prod, 4, '0', STR_PAD_LEFT);
	                        $items = item::search(['upc' => $ndc]);	
			} else {
                             return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $ndc was not formatted as expected for Colorado, expect label-prod-package formatted code."]);
			}	
			//if no match throw immediate error for this row, dont try name
			if(count($items) == 0){
	                     return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $ndc was not found and this is a Colorado CSV that requires exact match"]);
			}
			
		} else if(strlen($ndc) > 0){ //if not required, use the regular ndc field, which for coleman is going to match, for everyone else, it may or may not match
			$ndc = str_pad($ndc, 9, '0', STR_PAD_LEFT);
			$items = item::search(['upc' => $ndc]);
		}


		if(count($items) == 0){ 
			$query = "SELECT item.*, item.id as item_id, item.name as item_name, item.description as item_description
                                        FROM item
                                        USE INDEX (name_fulltext)
                                        WHERE `item`.`archived` = 0
                                        AND `name` LIKE ".$this->db->escape(str_replace(" ","%",$name)).
                                        " ORDER BY item.updated DESC
                                        LIMIT 1";
			$temp_items = $this->db->query($query);
			$looked_up_by_name = true;
			if(count($temp_items->result()) > 0){
				$items[] = $temp_items->result()[0];
			}
		}

		//If the NDC does not yet exist, try to create a new drug with it.
		if (count($items) == 0)
		{
			//We can only create a drug if we were provided a drug name.
			if (!$name OR strlen($name) === 0)
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $ndc was not found and no name field was provided (column can be drug.generic, Drug Name, Drug Label Name"]);
			if(!$ndc OR strlen($name) === 0)
				return self::$bulk['alerts'][] = array_merge($data, ["Row $row: $name was not found and no ndc field was provided"]);

			$upc = '';

			$drug = (object) [ //these qualities, plus upc (see past next if/else) will always be added
				'updated'     => gmdate(DB_DATE_FORMAT),
				'type'			  => 'medicine',
				'name' 			  => $name,
				//'description'	=> ($description ?: $name)." (Rx ".($description ? 'Brand' : 'Generic').")",
			];

			if(self::isV2()){ //V2 is only one where ndc is dash-separated
				list($label, $prod) = explode('-', $ndc); //may need to pad the NDC with leading 0s into the 5-4 format so split it apart.
				$upc = str_pad($label, 5, '0', STR_PAD_LEFT).str_pad($prod, 4, '0', STR_PAD_LEFT);

			} else {
				$upc =  substr($ndc, 0, 9);
			}

			$drug->price = $price ? $price : 0;
			$drug->price_date = $price_date ? $price_date : '0000-00-00 00:00:00';
			$drug->price_type = $price_type? $price_type : '';
			$drug->upc = $upc;
			$drug->mfg = $mfg;
			$drug->url = $url;
			$built_description = ($description ?: $name)." (".$rx_otc." ".$brand_generic.")";
			$drug->description = $built_description;
			//Create the drug and store its id into an array
			$this->db->insert('item', $drug);
			$drug->id = $this->db->insert_id();
			$items[] = $drug;
		}

		//If the NDC has mutiple matches in our DB then something is wrong!
		if ((count($items) > 1) AND (!$looked_up_by_name))
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
		if(!self::isPharmerica()){ //If not Pharmerica, then use tracking number
			if(array_key_exists('donation', self::$bulk['quasi_cache']) AND (self::$bulk['quasi_cache']['donation'][0]->tracking_number == $tracking_num)){
				$donations = self::$bulk['quasi_cache']['donation'];
			} else {
				//look up by tracking number or date_str if it's Polaris and there's no tracking number
				if(self::isPolaris() AND !$tracking_num){
                                        $date_str =  $data[self::$bulk['date_str']];
					if((array_key_exists('date_str', self::$bulk['quasi_cache'])) AND (self::$bulk['quasi_cache']['date_str'] == $date_str)){
						$donations = self::$bulk['quasi_cache']['donation'];
					} else {
						//take date and look for it in the date_shipped window AND it has donor id for one Polaris
						$temp_donations = [];
						log::info('inventory::import DateTime::createFromFormat');
						$exact_date_obj = DateTime::createFromFormat('Y-m-d',$date_str);
						if($exact_date_obj){ //makes sure the date is formatted correctly
        	                                        $pharm_name = $data[self::$bulk['polaris_pharmacy_name']];
	                                                $pharm_id = org::search(['org.name' => $pharm_name])->id;
																									log::info('inventory::import DateInterval');
																									$day_before = $exact_date_obj->sub(new DateInterval('P1D'))->format('Y-m-d');
                                                	$day_after = $exact_date_obj->add(new DateInterval('P2D'))->format('Y-m-d'); // add two days here because you actually modified original object when subtracting
                                                	$temp_donations = $this->db->query("SELECT donation.*,  donation.id as donation_id
                                                               FROM (donation)
                                                                WHERE `donation`.`donor_id` = ".$pharm_id.
                                                                " AND `donation`.`date_shipped` BETWEEN '".$day_before." 00:00:00' AND '".$day_after." 23:59:59'");
                                                	if(count($temp_donations->result()) > 0){
                                                                $donations[] = $temp_donations->result()[0];
                                               		}
                                                	if(count($donations) == 0){
                                                        	return self::$bulk['alerts'][] = array_merge($data, ["NO POLARIS DONATIONS SHIPPED ON THIS DATE"]);
                                                	} else {
                                                        	self::$bulk['quasi_cache']['donation'] = $donations;
                                                	}

						} else {
                                                        return self::$bulk['alerts'][] = array_merge($data, ["DATE OBJECT NOT FORMATTED CORRECTLY YYYY-MM-DD"]);

						}

					}
				} else { //then its V2,Coleman, or Polaris without a tracking number in that row and it uses just tracking number
					$donations = donation::search(['tracking_number' => $tracking_num]);
                              		self::$bulk['quasi_cache']['donation'] = $donations;
				}
			}
		} else { //If pharmerica, lookup by dummy tracking number name
			//look up with pharmacy donor id and the placeholder name format ('Viewmaster_January_2018')
                        //if(array_key_exists('donation', self::$bulk['quasi_cache']) AND (self::$bulk['quasi_cache']['full_name'] == $full_name) AND (self::$bulk['quasi_cache']['donation']->tracking_number == 'Viewmaster_'.self::$bulk['pharmericaMonth'])){
                        if((!$is_new_facility) AND (array_key_exists('donation', self::$bulk['quasi_cache']))){
			       //echo "HERE";
				//flush();
				$donations = self::$bulk['quasi_cache']['donation'];
                        	                                //self::$bulk['quasi_cache']['donation'] = $donations[0];


			} else {

                        	$donations = donation::search(['donor_id' => $donor_id, 'tracking_number' => 'Viewmaster_'.self::$bulk['pharmericaMonth']]);
				self::$bulk['quasi_cache']['donation'] = $donations;
			}
		}

		//If donation is not in the DB then try to create it for V2 or Pharmerica ONLY
		if (count($donations) == 0) {
			if(self::isPharmerica() OR self::isV2()){
				$donation = [];

				if(self::isPharmerica()){ //Create fake Pharmerica
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
					$donors = org::search(['phone' => $donor_phone]);
					$donees = org::search(['phone' => $donee_phone]);

					self::$bulk['alerts'][] = ["Donation had to be created for a V2 Import with tracking number $tracking_num"];

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
						'donee_id' => $donees[0]->id,
						'tracking_number' => $tracking_num
					];
				}

				//Add new donation to DB
				$this->db->insert('donation', $donation);
				$donation->donation_id = $this->db->insert_id();
				$donations[] = $donation;
				self::$bulk['quasi_cache']['donation'] = $donations;
			} else { //if not v2 or Pharmerica, we're not creating new shipments
				return self::$bulk['alerts'][] = array_merge($data, ["Tracking number $tracking_num does not match database, please correct"]);
			}
		}

		$archived_date = '';
		if((strlen($archived) == 0) AND (!self::isV2())){ //on V2, we can leave archived blank because sometimes we mark accepted, whereas any other import we don't have this info
			$archived_date = date('Y-m-d H:i:s');
		} else {
			$archived_date = date::format($archived, DB_DATE_FORMAT);
		}

		$donor_qty = self::isV2() ? NULL : $qty;
		$donee_qty = self::isV2() ? $qty : NULL;

		//Ok Item should have exactly one drug and one donation/shipment at this point so we should be able to add
		self::create([
			'donation_id'	=> $donations[0]->donation_id,
			'item_id'     => $items[0]->id,
			'donor_qty'		=> $donor_qty,
			'donee_qty'	=> $donee_qty,
			'org_id'      => $donations[0]->donee_id,
			'price' 	 		=> $items[0]->price ? $items[0]->price : 0,
			'price_date' 	=> $items[0]->price_date ? $items[0]->price_date : '0000-00-00 00:00:00',
			'price_type' 	=> $items[0]->price_type,
			'exp_date'		=> date::format($exp, DB_DATE_FORMAT),
			'archived'		=> $archived_date //date::format($archived, DB_DATE_FORMAT),
		]);

		self::$bulk['upload'][] =
		[
			'row' => $row,
			'donation_id'	=> $donations[0]->donation_id,
			'item_id'     => $items[0]->id,
			'dispensed'		=> $qty,
			'ndc'        => $ndc,
			'verb'       => $qty > 0 ? 'increased' : 'decreased',
			'org_id'      => $donations[0]->donee_id,
			'price' 	 		=> $items[0]->price ? $items[0]->price : 0,
			'price_date' 	=> $items[0]->price_date ? $items[0]->price_date : '0000-00-00 00:00:00',
			'price_type' 	=> $items[0]->price_type,
			'exp_date'		=> date::format($exp, DB_DATE_FORMAT),
			'archived'		=> date::format($archived, DB_DATE_FORMAT),
		];


		//We use $archived to designate if the item was accept by the donee into inventory.  If it was accepted
		//then we need to increment our inventory of this drug by the qty we just added
		if ( ! $archived)
			self::increment(['org_id' => $donations[0]->donee_id, 'item_id' => $items[0]->id], $qty);
	}
}  // END OF CLASS
