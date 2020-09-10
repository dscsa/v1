<?php
class donation extends MY_Model
{

	//static $table = 'donation';


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
			'donation'				  => ['*, id as donation_id, COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created) as date_status'],
			'donation_items'		=> ['id as id, org_id, donee_qty, donor_qty, IF(exp_date = "0000-00-00", "", exp_date) as exp_date, lot_number, price, IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty) as quantity, ROUND(SUM(price * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty)), 2) as value, COUNT(donation_items.id) as count, archived', 'donation_id = donation.id'],
			'item' 					    => ['id as item_id, name as item_name, description as item_desc, type, mfg, upc, image, imprint, color, shape, size', 'id = donation_items.item_id'],
			'org as donee_org'  => ['name as donee_org, instructions as donee_instructions, license as donee_license, phone as donee_phone, street as donee_street, city as donee_city, state as donee_state, zipcode as donee_zip', 'id = donation.donee_id'],
			'org as donor_org'	=> ['name as donor_org, instructions as donor_instructions, license as donor_license, phone as donor_phone, street as donor_street, city as donor_city, state as donor_state, zipcode as donor_zip', 'id = donation.donor_id']
		);
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
			case 'partner_id':
				$this->db->select("IF(donation.donor_id = $value, 'Donation', 'Request') as donation_type");

				//Show all "Donated", but only "Requests" that have actually been sent
				//so as not to have a lot of empty Pickup Scheduled Donations cluttering
				$this->db->where("(donation.donor_id = $value OR donation.donee_id = $value AND date_shipped > 0)");
				break;

			case 'partner':
				$this->db->where("(donor_org.name LIKE '%$value%' OR donee_org.name LIKE '%$value%')");
				break;

			case 'status':

				if ($value == 'pending')
				{
					$pickup = self::_status('pickup');
					$received = self::_status('received');

					$this->db->having("(donation_type = 'Donation' AND ($pickup) AND count > 0) OR (donation_type = 'Request' AND ($received))");
				}
				else
				{
					$this->db->where('('.self::_status($value).')');
				}

				break;

			case 'tracking_number':

				//We don't store the first 7 digits of the 22 digit barcode in our system
				self::_where($field, substr($value, -15));

				break;

			case 'donation.id':

				$this->db->order_by($this->input->get('o') ?: 'donation_items.id',  $this->input->get('d') ?: 'desc');

				self::_where($field, $value);

				break;

				//TODO this is duplicated from model::item/inventory
			case 'upc':
				item::universal($value);
				break;

			default:
				self::_where($field, $value);
				break;
		}
	}

	function _status($status)
	{
		switch ($status)
		{
			case 'pickup'  : return "date_shipped = 0 AND date_received = 0 AND date_verified = 0";

			case 'shipped' : return "date_shipped  > 0 AND date_received = 0 AND date_verified = 0";

			case 'received': return "date_received > 0 AND date_verified = 0";

			case 'verified': return "date_verified > 0";
		}
	}

	//Called in the two step communication with the comm-calendar
	function pullLabelBlob($label_name)
	{
		try{
			$file_contents = file_get_contents('label/'.urldecode($label_name));
			echo base64_encode($file_contents);
			flush();
		}catch(Exception $e){
			echo "Error:";
			echo json_encode($e);
			flush();
		}
	}

	//Given a fifteen-digit format, return info for a given donation if exists
	function pullTrackingInfo($tracking_num)
	{

			preg_match('/([0-9]{15})/',$tracking_num,$m);

			if(count($m) == 0){

				echo "Error: No tracking number provided";

			} else {

				$query = "SELECT *  FROM `donation` WHERE `tracking_number` = '$tracking_num'";

				$donations = $this->db->query($query);

				$track = fedex::track($tracking_num); //pulled directly form the fedex library, will have address

				$res = array(
					'db_data' => $donations->result(),
					'fedex_data' => $track
				);

				echo json_encode($res);
			}

			flush();
	}


	// 1. Update items with a donation_id and price info
	// 2. Insert remaining qty (if any) into a new item
	// 3. Update donation's manifest when we add items
	function confirm($donation_id, $items, $registered, $qtys)
	{
		$qty_key = $registered."_qty";

		foreach ($items as $item)
		{
			if ( ! isset($qtys[$item->id]))
			{
				// debug(count($items));
				// debug(count($qtys));
				// debug($items);
				// debug($qtys);
				continue;
			}

			$qty = $qtys[$item->id];

			if ($qty === '0')
			{
				continue;
			}

			$update =
			[
				'donation_id'	=> $donation_id,
				 $qty_key		=> $qty,
				'price' 	 		=> $item->price,
				'price_date' 	=> $item->price_date,
				'price_type' 	=> $item->price_type,

			];

			//Donor's items are not accepted by default (except in CO)
			//Donee's items are accepted by default
			if ('donee' != $registered)
			{
				if ('CO' != data::get('state'))
					$update['archived'] = gmdate('c');
			}
			else
			{
				//Donor qty was added when inventory was added, we need to remove now since we are actually setting donee_qty
				$update['donor_qty'] = null;
			}

			inventory::update($update, $item->id);

			//if unknown (null) quantity or transfering more than inventory, assume we donate all of it
			//only in the case of a non-zero remainder do we need to create a new donation_item
			//Even if this is donee_qty the qty is by default set as donor_qty when inventory is added
			if ($update[$qty_key] !== null and $qty < $item->donor_qty)
			{
				$create =
				[
					'item_id'   => $item->item_id,
					'org_id'    => $item->org_id,
					'donor_qty' => $item->donor_qty - $qty,
					//Set date so item isn't thought to be pending inventory
					'created'   => $item->created
				];

				inventory::create($create);
			}
		}

		bkg::donation('manifest', $donation_id);
	}


	//Creates a manifest of an invidiual donation
	function individual_manifest($list, $donor_name){

		$file = $donor_name.gmdate('_d-m-Y').'_manifest.pdf';

		if( ! file::exists('label', $file))
		{
			try{
				$manifest = pdf::individual_manifest(file::path('label', $file), $list);
			}catch(Exception $e){
				return $e;
			}
		}

		return $file;
	}


	// Create the label if necessary.  Return the file path.
	// During donation::create task is run in background
	function label($donation, $label_only = FALSE)
	{

		$file = $label_only ? $donation->donor_org.gmdate('_d-m-Y').'_label.pdf' : self::reference($donation).'_label.pdf';

		if( ! file::exists('label', $file))
		{
			$label = fedex::label(file::path('label', $file), $donation);

			if($label['error'])
			{
				log::error("Fedex unable to make a shipping label because $label[error]");
				return "Fedex unable to make a shipping label because $label[error]";
			}

			//Donee boxes won't have a donation_id
			if (isset($donation->donation_id))
			{
				self::update($label['success'], $donation->donation_id);
			}

			$label = $label_only ? pdf::individual_label(file::path('label', $file), $donation) : pdf::label(file::path('label', $file), $donation, $label['success']['tracking_number']);

			if ($label['error'])
			{
				log::info("Pdf unable to make a shipping label because $label[error]");
				return "Pdf unable to make a shipping label because $label[error]";
			}

			log::info("Shipping label created");
		}

		return $file;
	}



	function pickup($donation_id, $start = '', $date = '', $location = '')
	{
		$donation = donation::find($donation_id);

		if (valid::submit('cancel pickup'))
		{
			$cancel = fedex::cancel_pickup($donation);

			if ($cancel['success'])
			{
				donation::update(['pickup_number' => 0, 'date_pickup' => 0], $donation_id);

				to::info(["pickup for donation $donation_id", "canceled"], 'to_default', "donations/$donation_id");
			}
			else
			{
				to::alert(["pickup ", "canceled because ".log::error($cancel['error'])], 'to_default', "donations/$donation_id");
			}
		}
		else if(valid::and_submit('schedule') OR $start)
		{
			$pickup = fedex::pickup($donation, $start, $date, $location);

			donation::update($pickup['success'], $donation_id);

			if ($pickup['success'])
			{
				admin::email('Pickup scheduled', log::info("Pickup Scheduled for $donation->donor_org on $date ".print_r($pickup['success'], true)));

				if (valid::form())
				{
					to::info(["pickup for donation $donation_id", "scheduled"], 'to_default', "donations/$donation_id");
				}
			}
			else
			{
				admin::email('Pickup NOT scheduled', log::error("Pickup not scheduled for $donation->donor_org on $date: ".print_r($pickup['error'], true)));

				if (valid::form())
				{
					to::alert(["pickup", "scheduled because $pickup[error]"], 'to_default', "donations/$donation_id");
				}
			}
		}

		return $donation;
	}

	function manifest($donation_id, $overwrite = true)
	{
		$this->db->order_by('item_name', 'asc');

		$per_page = result::$per_page;

		result::$per_page = 9999;

		$donation = donation::search(['donation_id' => $donation_id], 'donation_items.id');

		result::$per_page = $per_page;

		$file = donation::reference($donation).'_manifest.pdf';

		if ($overwrite OR ! file::exists('manifest', $file))
		{
			file::delete('manifest', $file);

			$manifest = pdf::manifest(file::path('manifest', $file), $donation);

			if ($manifest['success'])
			{
				log::info("Shipping manifest for donation $donation->donation_id created with ".count($donation)." items");
			}
			else
			{
				log::error("Unable to make a shipping manifest  for donation $donation->donation_id because $manifest[success] $manifest[error]".json_encode($manifest));
			}
		}
		else
		{
			log::info("Manifest for donation $donation->donation_id not created because it already exists");
		}

		return $file;
	}

/**
| -------------------------------------------------------------------------
| Track
| -------------------------------------------------------------------------
|
| Retrieves all pending transaction for a user, checks to
| see if FedEx has recieved them and then updates the status if so.
|
| @param string password, options
|
|
*/
	function track()
	{

		//Hack to temporarily suspend per page limit
		//$per_page = result::$per_page;

		//result::$per_page = 9999;


		//depending on time of day, check different date ranges
		//tracking is triggered around the start of every hour
		$curr_hour = intval(gmdate('H')) - 7; //server runs on GMT

		$cutoff_start = "";
		$cutoff_end = "";

		if(($curr_hour == 10) OR ($curr_hour == 15) OR ($curr_hour == 21)){ //before noon, check old labels
			$cutoff_start = date('Y-m-d H:i:s',strtotime('-5 year'));
			$cutoff_end = date('Y-m-d H:i:s',strtotime('-3 year'));
		} else if(($curr_hour == 8) OR ($curr_hour == 12) OR ($curr_hour == 16) OR ($curr_hour == 20) OR ($curr_hour == 22)) {
			$cutoff_start = date('Y-m-d H:i:s',strtotime('-3 year'));
			$cutoff_end = date('Y-m-d H:i:s',strtotime('-1 year'));
		} else {
			$cutoff_start = date('Y-m-d H:i:s',strtotime('-1 year'));
			$cutoff_end = date('Y-m-d H:i:s');
		}

		$all_recipients = self::_getAllRecipients();
		$high_priority_ids = self::_getStateRecipientsString('CA,CO', $all_recipients); //high priority
		$donee_condition = " IN (".$high_priority_ids.") ";

		if(($curr_hour == 7)
				OR ($curr_hour == 10)
				OR ($curr_hour == 13)
				OR ($curr_hour == 16)
				OR ($curr_hour == 19)
				OR ($curr_hour == 22)) $donee_condition = " NOT".$donee_condition; //less important but still do

		log::info("Tracking donations 1");

		$query = "SELECT donor_id, donee_id, tracking_number, date_pickup, date_shipped, donation.id as donation_id, donee_org.name as donee_org, donor_org.name as donor_org FROM donation JOIN org as donee_org ON donee_org.id = donation.donee_id JOIN org as donor_org ON donor_org.id = donation.donor_id WHERE date_received IS NULL AND date_verified IS NULL AND (donation.created BETWEEN '$cutoff_start' AND '$cutoff_end') AND tracking_number IS NOT NULL AND donee_id".$donee_condition."LIMIT 9999";
		//echo $curr_hour."<br>".$donee_condition."<br>".$query."<br>"; //TODO delete

		//This was hitting our limit and missing some donations
		//$query = "SELECT donor_id, donee_id, tracking_number, date_pickup, date_shipped, donation.id as donation_id, donee_org.name as donee_org, donor_org.name as donor_org FROM donation JOIN org as donee_org ON donee_org.id = donation.donee_id JOIN org as donor_org ON donor_org.id = donation.donor_id WHERE date_received IS NULL AND date_verified IS NULL AND (donation.created BETWEEN '$cutoff_start' AND '$cutoff_end') AND tracking_number IS NOT NULL LIMIT 9999";

		log::info("Tracking donations 2");

		$donations = $this->db->query($query);

		//print_r($donations);
		//return;


		log::info("Tracking donations 3 ".$this->db->last_query());

		//end of OS modifications
		//result::$per_page = $per_page;
		//End Hack

		log::info("Tracking donations 4");

		//Make each row a donation record with result(model_name)
		//This was causing a crash since all rows were prefetched: $donations = $donations->result('record');
		$count = 0;

		log::info("Tracking donations 5");

		$donations->_data_seek(0);

		log::info("Tracking donations 6");

	  while ($row = $donations->_fetch_object())
	  {
			if ( ! ($count % 100))
			  log::info("Tracking donations Loop $count");

		  $count++;

			$donation = new record();
			foreach ($row as $key => $value)
			{
					$donation->$key = $value;
			}

			log::info("About to call FedEx track on: ".$donation->tracking_number);

			$track = fedex::track($donation->tracking_number);

			log::info("Fedex:Track returned ".print_r($track, true));

			unset($track['success']['address']);//remoce rhe address property from track, otherwise _update will bug out

			$email = [$donation->donor_org, $donation->donation_id, $donation->fedex()];

			//Skip if there was an error
			if($track['error'])
			{
				log::info("Tracking Error on Number: ".$donation->tracking_number);
				log::info("Tracking Error Loop $count ".print_r($track, true));
				continue;
			}

			$stage = key($track['success']);

			//log::info("debug tracking $count $stage $donation->tracking_number ".print_r($email, true).print_r($donation, true));

			if ('date_received' == $stage)
			{
				log::info("Tracking donations - $count date_recieved");

				$email[] = $donation->donee_org;

				$query = "SELECT COUNT(*) as count FROM donation_items WHERE donation_id = $donation->donation_id";
				$donation_items = $this->db->query($query)->result();
				if ($donation_items[0]->count)
				{
					org::email($donation->donor_id, 'email_received_items', $email); //removed attachment at George's request on 2019-05-13. 4th argument: file::path('manifest', donation::reference($donation).'_manifest.pdf')
				}
				else
				{
					org::email($donation->donor_id, 'email_received_no_items', $email);
				}

				self::_update($track['success'], $donation->donation_id);
			}

			//Check if already shipped because FedEx can send this stage multiple times - but only send emails the first time!
			if ('date_shipped' == $stage AND ! (int) $donation->date_shipped)
			{

				log::info("Tracking donations - $count date_shipped");

				//Exclude TLC from shipped emails.
				$org_id = $donation->donee_id == 608 ? 32 : $donation->donee_id;

				log::info("Sending shipped email to $org_id for ".$donation->fedex());

				admin::email('Debug Erroneous Shipped Emails', print_r($donation, true)." ".print_r($track, true));

				org::email($org_id, 'email_shipped', $email);

				self::_update($track['success'], $donation->donation_id);
			}

			//Oops looks like what we thought was shipped wasn't shipped after all.
			if ('order_created' == $stage AND (int) $donation->date_pickup AND (strtotime($donation->date_pickup) + 4200 <= time()))
			{
				log::info("Tracking donations - $count order_created");

				admin::email('email_missed_pickup', $email);

				//TODO hardcoded 9am time, caused problems for Olive Vista 60487 ~11/28/2014
				self::pickup($donation->donation_id, strtotime('09:00:00'));
			}
		}
		log::info("Tracked $count donations");
	}

	function reference($donation)
	{
		if (count($donation) == 0)
			return;

		$donation = count($donation) == 1 ? $donation : $donation[0];

		return 'D'.$donation->donor_id.'R'.$donation->donee_id.'T'.$donation->donation_id.ucfirst(substr(ENVIRONMENT, 0, 1));
	}

	function _getAllRecipients(){
		$query = "SELECT * FROM org WHERE approved != ''";
		return $this->db->query($query)->result();
	}


	function _getStateRecipientsString($state, $all_orgs){
		$ids = [];

		foreach ($all_orgs as $org) {
			if(strpos($state, $org->state) !== false){
				$ids[] = $org->id;
			}
		}

		$id_string = join(",",$ids);
		return $id_string;
	}


}  // END OF CLASS
