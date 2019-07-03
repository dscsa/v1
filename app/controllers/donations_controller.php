<?php

class Donations_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Index
| -------------------------------------------------------------------------
|
| This function allows users to search through all their donations and
| defaults to displaying all pending (uncompleted donations).  Search
| criteria is a bit mroe prescriptive with select boxes on many items
|
| Acessible through the donations link on the top navigation bar
|
|
*/

	function index()
	{
		user::login($org_id);
		$query = result::fields
		(
			['Partner', 'partner', 'partner()'],
			['Type', 'donation_type'],
			['Items', 'count'],
			//['Value', 'value', 'dollar(value)'],
			['Tracking', 'tracking_number', 'fedex()'],
			['Status', 'date_status', 'status()', ['After' => 'input(date)', 'Before' => 'input(date)']],
			['', '', 'button(donations/{donation_id}, Show&nbsp;Details)']
		);

    $order = result::order() ?: 'donation.updated';
		$offset = result::offset();
		$per_page = result::per_page();

		$q = $this->db
		->select("
			IF( donation.donor_id = $org_id, donee_org.name, donor_org.name) as partner,
			donee_id, donee_org.name as donee_org, donee_org.license as donee_license, donee_org.phone as donee_phone, donee_org.street as donee_street, donee_org.city as donee_city, donee_org.state as donee_state, donee_org.zipcode as donee_zip,
			donor_id, donor_org.name as donor_org, donor_org.license as donor_license, donor_org.phone as donor_phone, donor_org.street as donor_street, donor_org.city as donor_city, donor_org.state as donor_state, donor_org.zipcode as donor_zip,
			IF( donation.donor_id = $org_id, 'Donation', 'Request' ) as donation_type,
		  (SELECT COUNT(*) FROM donation_items WHERE donation.id = donation_items.donation_id) as count,
			tracking_number,
			COALESCE( date_shipped, date_received, date_verified, donation.created ) as date_status,
			date_shipped, date_received, date_verified, date_pickup,
			donation.id as donation_id"
		, false)
		->from('donation')
		->join('org as donor_org', 'donation.donor_id = donor_org.id')
		->join('org as donee_org', 'donation.donee_id = donee_org.id')
		->where("donation.donor_id = $org_id OR (donation.donee_id = $org_id AND date_shipped > 0)")
		->order_by($order, result::dir() ?: 'desc')
		->limit($per_page, $offset);

		if (isset($_POST['tracking_number']))
		  $q->where('tracking_number LIKE "%'.$_POST['tracking_number'].'"');

		$q = $q->get();

		if ( ! $q) {
			echo $this->db->last_query();
			echo $this->db->_error_message();
			return;
	  }

		$search = new result($q->result('record'));

		$search->next = $q->num_rows == $per_page ? $offset/$per_page + 2: false;
		$search->prev = $offset ? $offset/$per_page : false;

		if (data::post() AND count($search) == 1)
		{
			to::url("donations/{$search[0]->donation_id}");
		};

		$query['status'] = 'pending';

		$v = array
		(
			//'tracking_numbers' => donation::options('{donor_org} - {tracking_number}', $query, site_url('donations/{donation_id}'), 'Pending Quick Links...'),
			'results' => $search
		);

		view::full('donations', 'search donations', $v);
	}


/**
| -------------------------------------------------------------------------
| About
| -------------------------------------------------------------------------
|
| Page that displays the 6 step donation flow chart for each transaction
| and provides partner and drug informaiton, user messages, and links
| to move to the next stage.  Page is accessed from link provided in that
| status column on the transaction page. Links to this page are also provided
| to this page in email alerts concerning donation progress.
|
| After donation is complete this page allows both parties to leave each other feedback
| Right now only one feedback rating can be left at a time.
|
| @param int donation_id, required or page will show error
| @param string category, five types of feedback are accepted
| clinic, donor, item as described, communication, and shipping
| @param int score, five star rating left by user for the category listed above
*/

	function about($donation_id)
	{
		user::login($org_id);

		$items = data::post('items');

		$this->load->helper('download');
		$error_filename = "import_errors";
		$filepath = $_SERVER["DOCUMENT_ROOT"].'/'.$error_filename.'.csv';

		$v =
		[
			'none'			  => html::note('NO DONATION ITEMS MATCH YOUR CRITERIA'),
			'donation_id' => $donation_id
		];

		if ($items and (valid::and_submit('add') or valid::and_submit('save quantity')))
		{
			self::_about($org_id, $donation_id, $items);

			$v['message'] = html::info('Saved! Donation was updated');
		}

		//Submit is programmatic so no button e.g., Search can have been pressed
		//POST's Upload Value will not be set unless form_validation is run.
		if (valid::and_submit('import'))
		{

			item::csv('inventory', 'process');

			$success = '';
			$error_arr = [];
			$num_errors = 0;

			if ((count(inventory::$bulk['alerts']) > 6) || ((count(inventory::$bulk['alerts']) > 1) && (strlen(inventory::$bulk['pharmericaMonth']) == 0)))
			{

				$success .= 'Following unique errors:';
				$output = fopen($filepath, 'w');

				for($i = 0; $i < count(inventory::$bulk['alerts']); $i++){
					$error_text = array_values(array_slice(inventory::$bulk['alerts'][$i], -1))[0];
					if(strpos($error_text,"Donation had to be created") === false){
						if(strpos(strtolower($error_text), "beyond row limit") === false){
							$num_errors += 1;
						}
						fputcsv($output, inventory::$bulk['alerts'][$i]);
					}
					if(!in_array($error_text, $error_arr) AND strlen($error_text) > 0 AND $error_text !== 'error'){
						$success .= '<br>'.$error_text;
						$error_arr[] = $error_text;
					}
				}
				fclose($output);
			}

			if(count(inventory::$bulk['upload']) > 0){ //if successful at processing, then actually add them here

				$qty_key = data::post('donation_type') == 'Donation' ? 'donor_qty' : 'donee_qty';

				//Reverse so that display order is the same as the CSV
				for($row = count(inventory::$bulk['upload']) - 1; $row >= 0; --$row)
				{
					$data = inventory::$bulk['upload'][$row];

					inventory::create([
						'donation_id'	=> $donation_id,
						'item_id'     => $data['item_id'],
						$qty_key			=> $data['dispensed'],
						'org_id'      => $org_id,
						'price' 	 		=> $data['price'],
						'price_date' 	=> $data['price_date'],
						'price_type' 	=> $data['price_type'],
						'exp_date'		=> $data['exp_date'],
						'archived'		=> $data['archived']
					]);

					if ($qty_key == 'donee_qty' AND ! $data['archived'])
					{
						//TODO Good enough, but this is not quite right. If the first NDC brings total to -2, then
						//this will goto zero, however then if the next NDC says increase by 1 then it will goto
						//1 and show rather than being at -1 which would still be hidden
						inventory::increment(['org_id' => $org_id, 'item_id' => $data['id']], $data['dispensed']);
					}
					$success = "Row ".($row+2).": $data[ndc] was added with quantity $data[dispensed]<br>".$success;
				}
			}

			$v['message'] = html::info('Donation items imported successfully<br><br>'.$success, '', ['style' => 'text-align:left']);

		}

		if(valid::submit('Get Errors')){
				ob_clean();
				force_download("tmp_import_errors.csv",file_get_contents($filepath)); //use helper function

		}

		//Show all, i.e. don't filter results based on this new ndc
		$upc = data::post('upc');
		unset($_POST['upc']);

		$query = result::fields
		(
			['Name', 'item_name', 'item_pop()'],
			['NDC/UPC', 'upc'],
			['Price', '', 'dollar(price)'],
			['Value', '', 'dollar(value)'],
			['Donor', '', 'donor_qty_input()'],
			['Donee', '', 'donee_qty_input()']
		);

		$query +=
		[
			'donation_id' => $donation_id,
			'partner_id' => $org_id,
		];

		$donation = donation::search($query, 'donation_items.id');

		//Don't reverify a donation.  Only verify if donee, button == save quantity, and not already verified
		if ($org_id == $donation->donee_id and valid::submit('save quantity') and ! (int) $donation->date_verified)
		{
			$donation[0]->date_verified = gmdate('c');

			donation::update(['date_verified' => $donation[0]->date_verified], $donation->donation_id);

			admin::email("Donation Verified", html::link("donations/$donation->donation_id", "Donation $donation->donation_id")." with tracking number ".$donation->fedex()." from $donation->donor_org was verified");
		}

		$v['results'] = $donation;

		//Unfortunately, we have to add the new item after
		//donation::search so two users can add items at the same time
		//otherwise one loads the other item with null quantity which
		//overrides the actual quantity once it is saved.  This happened
		//in the Oregon Pharmacy on the first Saturday.
		if (valid::submit('add'))
		{
			$item = item::find(['upc' => $upc]);

			//Make sure item has all the donation info that would have been
			//Want to move over tracking number, partner name and address, status date
			//but not others.  This is a little hacky but seemed like the only feasible way.
			$exclude = ['donor_qty', 'donee_qty', 'lot_number', 'exp_date', 'archived', 'quantity', 'count', 'value'];
			foreach($v['results'][0] as $key => $val) {
				if ( ! $item->$key && ! in_array($key, $exclude)) {
					$item->$key = $val;
				}
			}
			$item->id = 'NEW';

			//Are there actually items or is this just an empty donation. If empty
			//donation merge the two so it look like a normal one item result.
			if ($v['results'][0]->id)
				$v['results']->prepend($item);
			else
				$v['results'][0] = $item;

			$v['message'] = html::info("Enter item's quantity. Be sure to save!");
		}

		view::full('donations', "Donation #$donation_id ", $v);
	}

	function _about($org_id, $donation_id, $items)
	{
		$olds = data::post('olds');
		$save = valid::submit('save quantity');

		foreach ($items as $id => $item)
		{
			$old = $olds[$id];
			//Format required for CI's db->update_batch()
			$items[$id]['id'] = $id;

			if ($id == 'NEW')
			{
				$donation_item = item::find(['item.id' => $old['item_id']]);

				$items[$id]['id'] = inventory::create([
					'donation_id'	=> $donation_id,
					'item_id'     => $donation_item->item_id,
					'org_id'      => $org_id,
					'price' 	 		=> $donation_item->price,
					'price_date' 	=> $donation_item->price_date,
					'price_type' 	=> $donation_item->price_type,
					'archived'		=> gmdate('c')
				]);
			}

			if (isset($item['donor_qty']) && $item['donor_qty'] === '')
			{
				$items[$id]['donor_qty'] = null;
			}

			if (isset($item['lot_number']) && $item['lot_number'] === '')
			{
				$items[$id]['lot_number'] = null;
			}

			if (isset($item['exp_date']))
			{
				$items[$id]['exp_date'] = date::format($item['exp_date'], DB_DATE_FORMAT);
			}

			//Nothing else special to do when adjusting donor qtys
			if ( ! isset($item['donee_qty']))
			{
				continue;
			}

			if ($item['donee_qty'] === '')
			{
				$items[$id]['donee_qty'] = null;
			}

			//unchecked checkboxes do not set a value.  if not accepted, the items should be archived
			if ( ! isset($item['archived']))
			{
				$items[$id]['archived'] = $item['archived'] = gmdate('c');
			}

			// a quantity of = means that donor qty was correct, ignore when searching or adding items
			if ($item['donee_qty'] == '=')
			{
				$items[$id]['donee_qty'] = $item['donee_qty'] = $save ? $old['donor_qty'] : null;
			}

			//Donee quanties cannot be easily changed. They are aggregated by NDC to make a live inventory
			//we need to track changes of the 5 possible scenarios.  If NDC does not exist in the inventory then we need to create it
			//1. Rejected to rejected with or without quantity quantity change -> Update donation_item's donee_qty, which will be done automatically below
			//2. Accepted to accepted with or without quantity quantity change -> Update donation_item's donee_qty, increment inventory's NDC by (new qty - old qty)
			//3. Accepted to rejected with or without quantity change -> Update donation_item's donee_qty, decrement inventory's NDC by old qty
			//4. Rejected to accepted with or without quantity change -> Update donation_item's donee_qty, increment inventory's NDC by new qty

			//Scenario 1 -> Already part of batch update.
			if ( (int) $item['archived'] and (int) $old['archived'])
			{
				continue;
			}

			//Scenario 2 -> Increment by (new - old)
			if ( ! (int) $item['archived'] and ! (int) $old['archived'])
			{
				$increment = $item['donee_qty'] - $old['donee_qty'];
			}

			//Scenario 3 - Decrement by old
			if ( (int) $item['archived'] and ! (int) $old['archived'])
			{
				$increment = -$old['donee_qty'];
			}

			//Scenario 4 - Increment by new
			if ( ! (int) $item['archived'] and (int) $old['archived'])
			{
				$increment = $item['donee_qty'];
			}

			if ($increment == 0)
			{
				continue;
			}

			inventory::increment(['org_id' => $org_id, 'item_id' => $old['item_id']], $increment);
		}

		//Help debug Better Health Pharmacy 0 quantity checked Error
		/*log::info('POST::Items');
		log::info(data::post('items'));
		log::info('POST::Olds');
		log::info(data::post('olds'));
		log::info('Items');
		log::info($items);*/

		//Batch update since one by one took too long when > 100 items
		$this->db->update_batch('donation_items', $items, 'id');

		//Get rid of anything implicitly deleted
		$this->db->delete('donation_items', '(donor_qty = 0 AND donee_qty IS NULL) OR (donee_qty = 0 AND donor_qty IS NULL) OR (donee_qty = 0 AND donor_qty = 0)');
	}

/**
| -------------------------------------------------------------------------
|  Start
| -------------------------------------------------------------------------
|
| This is first page that appears upon login and is the first link on the top navigation
| Function shows recent messages to user and proposes any donees that have matching
| requests to the current user's inventory.
|
| Page currently is pickes up post arrays from other functions (error, add_inv, add_req) that
| should be handled within their respective functions since they are unrelated to finding matches.
| Also because the security question is a new addition to our site, we check to see if an old user does
| not yet have one and require them to enter it.
|
| @param int clinic_id, options
| @param int modify_quantity, options
| @param string orderby , field by which the matches table is sorted
| @param int modify_value, options
|
*/

	function start()
	{
		user::login($org_id);

		$count = count(inventory::search(['donation_items.org_id' => $org_id]));

		result::fields
		(
			['Recipient', '', 'partner()'],
			['Location', '', 'concat(donee_city, donee_state)'],
			['Donations', '', 'concat(donations)'],
			['Matches', '', "bar(matches, $count)"],
			['', '', 'start_donation()']
		);

		$v['matches'] = inventory::search(['donation_items.archived >=' => 0, 'org_id' => $org_id, 'donee_id' => 0], 'org.id');

		view::full('donations', 'start new donation', $v);
	}


/**
| -------------------------------------------------------------------------
|  Confirm
| -------------------------------------------------------------------------
|
| This funciton creates a new donation in the transaction table, subtracts inventory from the
| clinics and reduces the request amount of the clinic. A message is sent to both users and
| and an email is sent to the clinic informing them of the match.  Function is called after
| donor enters amount on the matches page and confirms on next page.  Once completed,
| the donor must enter shipping details of the package and the donation progress can be
| tracked on the donation status page.
|
| Note suffixes med and sup are used in form data so that multiple donations can be made
| at one time on the find matches page.  Though mixing donations of supplies and medicines
| causes an error since page cannot display both at same time.
|
*/
	function confirm($donee_id)
	{
		user::login($donor_id);

		$query = result::fields
		(
			['Name', 'item_name', 'item_pop()', 'input()'],
			['', '', '', ['Description' => 'input()']],
			['', '', '', 'submit(Search)'],
			['Updated', 'updated', 'date(updated)', ['Before' => 'input(date)', 'After' => 'input(date)']],
			['You have', 'donor_qty', '', ''],
			['Requested', '', 'requested()'],
			['Amount', '', "qty_donate()"]
		);

		$query += ['donor_id' => $donor_id, 'donee_id' => $donee_id];

		$this->db->order_by($this->input->get('o') ?: 'donation_items.id',  $this->input->get('d') ?: 'desc');

		$items = inventory::search($query, 'donation_items.id');

		if (valid::form())
		{
			self::_confirm($donor_id, $donee_id, $items);
		}

		$donations = donation::options('Donation #{donation_id} - {tracking_number}', ['partner_id' => $donor_id], '{donation_id}');

		$donations['new'] = "I don't have a shipping label, make me one!";

		$v = array
		(
			'donations' =>  $donations,
			'items'     =>  $items,
			'donee'     =>  org::find($donee_id)
		);

		view::full('donations', "confirm donation items", $v);
	}

	function _confirm($donor_id, $donee_id, $items)
  {
		$donation_id = data::post('donation_id');

		if ($donation_id == "new")
		{
			$donation_id = donation::create(compact('donor_id', 'donee_id'));

			bkg::donation('pickup', $donation_id, strtotime('09:00:00'));

			$donation = donation::find($donation_id);

			donation::label($donation);
		}

		// if the donee is adding items to the donation then they are the DONOR
		// in this situation and we want to make sure we add to DONEE QTY
		donation::confirm($donation_id, $items, $donor_id == $donee_id ? 'donee' : 'donor', data::post('quantities'));

		to::info(['Success!', 'Items added to the donation'], 'to_default', "donations/$donation_id");
	}

	/**
	| -------------------------------------------------------------------------
	|  Individual Donation
	| -------------------------------------------------------------------------
	|
	| This funciton is called through the API, with information to
	| create a donation from an individual
	|
	*/
	function individual_donation(){

			$email = text::get('email_individual_donation_success');
			$attachments = array();

			try{
				$raw_data = (json_decode(urldecode(file_get_contents('php://input'))));
			}catch(Exception $e){ //with this error, we can't even email them
				self::_send_error_email($e);
				return;
			}

			$donation = self::_build_individual_donation($raw_data);

			$label_and_thanks_file = donation::label($donation,TRUE); //filepath to the label
			$manifest_file = donation::individual_manifest($donation->donor_manifest, $donation->donor_org);

		  //if either filenames don't include .pdf, there's been an error
			if((strpos($label_and_thanks_file, '.pdf') === false) OR (strpos($manifest_file, '.pdf') === false)){

				$error_text = '';
				if(strpos($label_and_thanks_file, '.pdf') === false) $error_text .= $label_and_thanks_file.'      ';
				if(strpos($manifest_file, '.pdf') === false) $error_text .= $manifest_file;

				$email = self::_send_error_email($error_text,array($raw_data,$donation), array('Raw JSON', 'Donation Object'));	//send email to debug team with full error message so we can manually create, while debugging whatever went wrong

			} else { //only attach if no errors

				$attachments = array('label/'.$label_and_thanks_file, 'label/'.$manifest_file);

			}

			$view = $this->load->view('common/email', ['body' => $email[1], 'user' => $donation->donor_org], true);

			admin::email($email[0],$view, $donation->donor_email,$attachments);

		}


/*
| -------------------------------------------------------------------------
| Helper Functions
| -------------------------------------------------------------------------
| This section contains helper functions to support the user called functions above
|
| Because helper functions should not be called by the user directly, put an
| underscore "_" before each function name.  For example, function _helper_function()
|
*/


//Sends an email to the debuggers on call, with as much relavant debugging info as possible
function _send_error_email($error_text,$debugging_objects = array(), $debugging_tags = array()){
	$email_subject = '[URGENT ACTION] Error with Individual Donation Request';
	$email_body = "";

	$email_body .= '<b>ERROR MESSAGE</b>: '.$error_text.'<br><br>';

	foreach ($debugging_objects as $i => $obj) {
		$email_body .= '<br><b>'.$debugging_tags[$i].':</b><br><br>';
		$email_body .= json_encode($obj);
	}

	$email_body .= '<br><br><b>Raw POSTed data:</b><br><br>';
	$email_body .= urldecode(file_get_contents('php://input'));

	$debug_accounts = array('omar@sirum.org', 'adam@sirum.org');

	foreach ($debug_accounts as $i => $email_addr) {
		admin::email($email_subject,$email_body, $email_addr,array());
	}

	return text::get('email_individual_donation_failure');
}


//Takes the json from the POST
function _build_individual_donation($raw_data){

		$med_list = $raw_data->ListOfDonatedMedicine; //an array of jsons

		$donor_name = $raw_data->MyName->FirstAndLast;
		$donor_email = $raw_data->MyEmail;
		$donor_addr_obj = $raw_data->MyAddress;
		$donor_full_addr = $donor_addr_obj->FullAddress;
		$donor_street = $donor_addr_obj->StreetAddress;
		$donor_city = $donor_addr_obj->City;

		//Can't use state name, have to use abbreviation bc Fedex otherwise gets alittle freaky
		$donor_state = '';

		if(array_key_exists($donor_addr_obj->State, self::$states)){ //if not, it'll throw a fedex error we catch down the line
			$donor_state = self::$states[$donor_addr_obj->State];
		}

		$donor_zipcode = $donor_addr_obj->PostalCode;

		$dummy_donor_id = 818; //this is just for utc time lookup for pickup, but there'll be no pickups so doesnt matter
		$donor_instructions = "";

		$donor_payment_obj = $raw_data->Order->LineItems;
		$donated_amount = '';

		foreach ($donor_payment_obj as $i => $item) {
			if(strpos(strtolower($item->Name),"shipping cost") === FALSE){
				if($item->Amount != "0") $donated_amount = " of $".$item->Amount;
			}
		}

		$raw_label_text = text::get('label_thank_you');
		$label_text = array();

		//Replace any variables in the thank you note
		foreach ($raw_label_text as $i => $line){
			if(strpos($line,"<donor_name>") !== FALSE) $line = str_replace("<donor_name>",$donor_name, $line);
			if(strpos($line,"<donation_date>") !== FALSE) $line = str_replace("<donation_date>",date("F j, Y"), $line);
			if(strpos($line,"<donor_addr>") !== FALSE) $line = str_replace("<donor_addr>",$donor_full_addr, $line);
			if(strpos($line,"<donor_email>") !== FALSE) $line = str_replace("<donor_email>",$donor_email, $line);
			if(strpos($line,"<donated_amount>") !== FALSE) $line = str_replace("<donated_amount>",$donated_amount, $line);

			$label_text[] = $line;
		}


		$donation = (object) array
		(
			'donation_id'  => 'individual',
			'donor_id' 		=> $dummy_donor_id, //need this only for utc time lookup
			'donor_org' 	=> $donor_name,
			'donor_street' => $donor_street,
			'donor_city' 	=> $donor_city,
			'donor_state' 	=> $donor_state,
			'donor_zip' 	=> $donor_zipcode,
			'donor_instructions' => $donor_instructions,

			//Information for the Wyoming Medication Donation Program
			'donee_instructions' => '',
			'donee_org' 	=> "Wyoming Medication Donation Program",
			'donee_street' => "2300 Capitol Ave, Suite B27",
			'donee_city' 	=> "Cheyenne",
			'donee_state'	=> "Wyoming",
			'donee_zip' 	=> "82002",

			'label_text' => $label_text,
			'donor_email' => $donor_email,
			'donor_full_addr' => $donor_full_addr,
			'donor_manifest' => $med_list
		);

		return $donation;

}



//Need this in order to turn Cognito's state name format into abbreviations
static $states = array(
	'Alabama'=>'AL',
	'Alaska'=>'AK',
	'Arizona'=>'AZ',
	'Arkansas'=>'AR',
	'California'=>'CA',
	'Colorado'=>'CO',
	'Connecticut'=>'CT',
	'Delaware'=>'DE',
	'Florida'=>'FL',
	'Georgia'=>'GA',
	'Hawaii'=>'HI',
	'Idaho'=>'ID',
	'Illinois'=>'IL',
	'Indiana'=>'IN',
	'Iowa'=>'IA',
	'Kansas'=>'KS',
	'Kentucky'=>'KY',
	'Louisiana'=>'LA',
	'Maine'=>'ME',
	'Maryland'=>'MD',
	'Massachusetts'=>'MA',
	'Michigan'=>'MI',
	'Minnesota'=>'MN',
	'Mississippi'=>'MS',
	'Missouri'=>'MO',
	'Montana'=>'MT',
	'Nebraska'=>'NE',
	'Nevada'=>'NV',
	'New Hampshire'=>'NH',
	'New Jersey'=>'NJ',
	'New Mexico'=>'NM',
	'New York'=>'NY',
	'North Carolina'=>'NC',
	'North Dakota'=>'ND',
	'Ohio'=>'OH',
	'Oklahoma'=>'OK',
	'Oregon'=>'OR',
	'Pennsylvania'=>'PA',
	'Rhode Island'=>'RI',
	'South Carolina'=>'SC',
	'South Dakota'=>'SD',
	'Tennessee'=>'TN',
	'Texas'=>'TX',
	'Utah'=>'UT',
	'Vermont'=>'VT',
	'Virginia'=>'VA',
	'Washington'=>'WA',
	'West Virginia'=>'WV',
	'Wisconsin'=>'WI',
	'Wyoming'=>'WY'
);

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
