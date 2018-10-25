<?php
class Admin_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Index
| -------------------------------------------------------------------------
|
|
*/

function index()
{
	user::login($org_id, 'admin');

	if (valid::and_submit('import'))
	{
		item::csv('admin', 'import');
		if (admin::$bulk['alerts']) //There shouldn't be many errors, since we'll force name matching earlier on in workflow
		{
			echo "ERRORS IN CSV:<br>";
			for($i = 0; $i < count(admin::$bulk['alerts']); $i++){
				$row = admin::$bulk['alerts'][$i];
				echo "DONOR: ".$row[0]."; DONEE: ".$row[1]."; ERROR: ".$row[3]."<br>";
			}
			echo "Fix/remove the corresponding rows, then retry. To reload the page, hit enter in url bar, instead of refreshing.<br>";
		} else { //only generate labels if there are no errors

			$file_path_2d_arr = [];
			foreach (admin::$bulk['upload'] as $row => $data)
			{
				$donor_id = $data['donor_id'];
				$donee_id = $data['donee_id'];
				$num_labels = $data['num_labels'];
				$res = self::_index($donor_id, $donee_id, $num_labels, "donation"); //last field is label_type, which we assume is donation
				array_push($file_path_2d_arr, $res);
			}

			
			$final_file = pdf::merge($file_path_2d_arr);
			file::download('label',$final_file);
		}
	}


	if (valid::and_submit('create'))
	{
		self::_index();
	}

	$per_page = result::$per_page;

	result::$per_page = 9999;

	$v = array
	(
		'users' 		=> org::options('{org_name} ({registered})', [], '{org_id}'),
			'label_types'   =>
			[	'donation label' => 'Donation Label'
			,	'label only' 	  => 'Label Only'
			]
		);

		result::$per_page = $per_page;

		view::full('admin', 'Labels', $v);
	}

	//Actually generates the labels, barely modified version of the old _index function so
	//that it can take in values useful for mass label creation.
	function _index($donor_id = '', $donee_id = '', $num_labels = 0, $label_type = '')
	{
		$manual = false;

		//If manual, then these values are empty, so set them to appropriate values
		if(! $donor_id){
			$manual = true;
			$donor_id = data::post('donor_id');
		}
		
		if(! $donee_id){
			$donee_id = data::post('donee_id');
		}

		if(!$num_labels){
			$num_labels = 1;
		}

		if(!$label_type){
			$label_type = data::post('label_type');
		}

		$result_arr = [];
		
		
		for ($i = 0; $i < $num_labels; $i++){
			//create labels, return array of the pdf locations
			if ($label_type == 'label only')
			{
				$donor = org::find($donor_id);

				$donee = org::find($donee_id);

				//we need to fake a donation since we don't want to create one
				$donation = (object) array
				(
					'donation_id'  => 'none',
					'donor_id' 		=> $donor->org_id,
					'donor_org' 	=> $donor->org_name,
					'donor_street' => $donor->street,
					'donor_city' 	=> $donor->city,
					'donor_state' 	=> $donor->state,
					'donor_zip' 	=> $donor->zipcode,
					'donor_instructions' => $donor->instructions,
					'donee_instructions' => $donee->instructions,
					'donee_id' 		=> $donee->org_id,
					'donee_org' 	=> $donee->org_name,
					'donee_street' => $donee->street,
					'donee_city' 	=> $donee->city,
					'donee_state'	=> $donee->state,
					'donee_zip' 	=> $donee->zipcode
				);

				//Don't want to get a cached label
				file::delete('label', donation::reference($donation).'_label.pdf');
			}
			else
			{
				$donation_id = donation::create(compact('donor_id', 'donee_id'));

				//Orginially user tables were joined to that donation/index, record/donated, & record/destroyed could have full org tooltips.
				//the full query was taking 15-35secs to execute, so removing tooltips from these views.  Instead only get full org/user data
				//when are are looking at a specific donation!
				//Even with above donation query of ~200 items was taking 1 sec, and without it, .05 secs.  This makes a different for logging speed.
				/*$this->db->select('donee_user.name as donee_user, donee_user.email as donee_email, donor_user.name as donor_user, donor_user.email as donor_email');*/
	      $this->db
				  ->select('(SELECT name  FROM user WHERE user.org_id = donee_org.id ORDER BY user.current_login DESC LIMIT 1) as donee_user')
					->select('(SELECT name  FROM user WHERE user.org_id = donor_org.id ORDER BY user.current_login DESC LIMIT 1) as donor_user')
					->select('(SELECT email FROM user WHERE user.org_id = donee_org.id ORDER BY user.current_login DESC LIMIT 1) as donee_email')
					->select('(SELECT email FROM user WHERE user.org_id = donor_org.id ORDER BY user.current_login DESC LIMIT 1) as donor_email');
				/*$this->db->join('user as donee_user', 'donee_user.org_id = donee_org.id', 'left');
				$this->db->join('user as donor_user', 'donor_user.org_id = donor_org.id', 'left');
				$this->db->join('user as donee_last', 'donee_last.org_id = donee_org.id AND donee_user.current_login < donee_last.current_login', 'left');
				$this->db->join('user as donor_last', 'donor_last.org_id = donor_org.id AND donor_user.current_login < donor_last.current_login', 'left');

				$this->db->where('donee_last.id is null');
				$this->db->where('donor_last.id is null');*/

				$donation = donation::find($donation_id);

				//echo $this->db->last_query();
				//print_r($donation);
			}

			$file = donation::label($donation); //filepath to the label	
			//TODO: Handle errors here if theres a FEDEX error, and don't add it to array as a filename.	
			array_push($result_arr,$file);
		}

		if($manual){
			//download directly
			file::download('label', $file);
		} else {
			//return array of filenames to be used in merging & then downloading
			return $result_arr;
		}
		
	}

	function deprecated_index()
	{

		$donor_id = data::post('donor_id');

		$donee_id = data::post('donee_id');

		if ( data::post('label_type') == 'label only')
		{
			$donor = org::find($donor_id);

			$donee = org::find($donee_id);

			//we need to fake a donation since we don't want to create one
			$donation = (object) array
			(
				'donation_id'  => 'none',
				'donor_id' 		=> $donor->org_id,
				'donor_org' 	=> $donor->org_name,
				'donor_street' => $donor->street,
				'donor_city' 	=> $donor->city,
				'donor_state' 	=> $donor->state,
				'donor_zip' 	=> $donor->zipcode,
				'donor_instructions' => $donor->instructions,
				'donee_instructions' => $donee->instructions,
				'donee_id' 		=> $donee->org_id,
				'donee_org' 	=> $donee->org_name,
				'donee_street' => $donee->street,
				'donee_city' 	=> $donee->city,
				'donee_state'	=> $donee->state,
				'donee_zip' 	=> $donee->zipcode
			);

			//Don't want to get a cached label
			file::delete('label', donation::reference($donation).'_label.pdf');
		}
		else
		{
			$donation_id = donation::create(compact('donor_id', 'donee_id'));

			//Orginially user tables were joined to that donation/index, record/donated, & record/destroyed could have full org tooltips.
			//the full query was taking 15-35secs to execute, so removing tooltips from these views.  Instead only get full org/user data
			//when are are looking at a specific donation!
			//Even with above donation query of ~200 items was taking 1 sec, and without it, .05 secs.  This makes a different for logging speed.
			/*$this->db->select('donee_user.name as donee_user, donee_user.email as donee_email, donor_user.name as donor_user, donor_user.email as donor_email');*/
      $this->db
			  ->select('(SELECT name  FROM user WHERE user.org_id = donee_org.id ORDER BY user.current_login DESC LIMIT 1) as donee_user')
				->select('(SELECT name  FROM user WHERE user.org_id = donor_org.id ORDER BY user.current_login DESC LIMIT 1) as donor_user')
				->select('(SELECT email FROM user WHERE user.org_id = donee_org.id ORDER BY user.current_login DESC LIMIT 1) as donee_email')
				->select('(SELECT email FROM user WHERE user.org_id = donor_org.id ORDER BY user.current_login DESC LIMIT 1) as donor_email');
			/*$this->db->join('user as donee_user', 'donee_user.org_id = donee_org.id', 'left');
			$this->db->join('user as donor_user', 'donor_user.org_id = donor_org.id', 'left');
			$this->db->join('user as donee_last', 'donee_last.org_id = donee_org.id AND donee_user.current_login < donee_last.current_login', 'left');
			$this->db->join('user as donor_last', 'donor_last.org_id = donor_org.id AND donor_user.current_login < donor_last.current_login', 'left');

			$this->db->where('donee_last.id is null');
			$this->db->where('donor_last.id is null');*/

			$donation = donation::find($donation_id);

			//echo $this->db->last_query();
			//print_r($donation);
		}

		$file = donation::label($donation);

		file::download('label', $file);
	}

/**
 | -------------------------------------------------------------------------
 |  Accounts
 | -------------------------------------------------------------------------
 |
 |
 */

	function accounts()
	{
		user::login($org_id, 'admin');

		$query = result::fields
		(
			['Organization', 'org_name', 'link(admin/swap, user_id, org_name)', 'input()'],
			['Donor', 'date_donor', 'date(date_donor)', ['After' => 'input(date)', 'Before' => 'input(date)']],
			['Donee', 'date_donee', 'date(date_donee)', ['After' => 'input(date)', 'Before' => 'input(date)']],
			['Pending', 'num_pending', '', 'input()'],
			['Donations', 'num_donations', '', 'input()'],
			['Last Donation', 'last_donation', 'date(last_donation)', ['After' => 'input(date)', 'Before' => 'input(date)']]
		);

		$v['results'] = user::search($query, 'org.id');

		if ( ! $v['results']) {
			echo $this->db->last_query();
			echo $this->db->_error_message();
			return;
	  }

		view::full('admin', 'accounts', $v);
	}

/**
 | -------------------------------------------------------------------------
 |  Items
 | -------------------------------------------------------------------------
 |
 |
 */

	function items()
	{
		user::login($org_id, 'admin');
		$v = [];

		if (valid::form())
		{
			switch(data::post('button'))
			{
				case 'Import v2 Transactions':
					$partial = item::csv('inventory', 'import');

					$name = 'Completed Import Errors';

					if ($partial) {
						$name = 'Partial Upload Errors. Reupload file to resume';
						inventory::$bulk['alerts'][] = "Uploaded Rows ".($partial - 10000)."-$partial. Reupload file to resume at Row $partial.";
					}

					if (count(inventory::$bulk['alerts']) > 1)
					  file::download_csv($name, inventory::$bulk['alerts']);

					return;
				case 'Update NDCs':     item::csv('medicine', 'ndc', "\t"); break;
				case 'Update Prices':   item::csv('medicine', 'price'); break;
				case 'Update Images':   item::url('medicine', 'image'); break;
				case 'Update Imprints': item::url('medicine', 'imprint'); break;
			}

			if ( ! $v['message'])
				to::info([data::post('button'), 'completed successfully!']);
		}

		view::full('admin', 'items', $v);
	}

/**
 | -------------------------------------------------------------------------
 |  Metrics
 | -------------------------------------------------------------------------
 |
 |
 */

 /* OLD
 ', donation_items.archived = 0 as accepted_count,
 IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty) as donated_quantity,
 (donation_items.archived = 0) * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty) as accepted_quantity,
 donation_items.price * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty) as donated_value,
 (donation_items.archived = 0) * donation_items.price * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty) as accepted_value,
 donation_items.donor_qty as donor_qty,
 donation_items.donee_qty as donee_qty,
 donation_items.price * donation_items.donor_qty as donor_value,
 donation_items.price * donation_items.donee_qty as donee_value,
 donation_items.donee_qty, donation_items.donor_qty, donation_items.exp_date, donation_items.price,
 item.id as item_id, item.name as item_name, item.description as item_desc,
 item.type, item.mfg, item.upc, item.price as current_price';

 ', COUNT(donation_items.id) as donated_count,
 SUM(donation_items.archived = 0) as accepted_count,
 SUM(IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty)) as donated_quantity,
 SUM((donation_items.archived = 0) * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty)) as accepted_quantity,
 SUM(donation_items.price * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty)) as donated_value,
 SUM((donation_items.archived = 0) * donation_items.price * IF(donation_items.donee_qty is not null, donation_items.donee_qty, donation_items.donor_qty)) as accepted_value,
 SUM(donation_items.donor_qty) as donor_qty,
 SUM(donation_items.donee_qty) as donee_qty,
 SUM(donation_items.price * donation_items.donor_qty) as donor_value,
 SUM(donation_items.price * donation_items.donee_qty) as donee_value, donor_org.street as donor_street, donor_org.city as donor_city, donor_org.zipcode as donor_zip, donee_org.street as donee_street, donee_org.city as donee_city, donee_org.zipcode as donee_zip';
 */

   function metrics($items = false)
	{
		user::login($org_id, 'admin');

		set_time_limit(0);
		$this->output->enable_profiler(FALSE);
		$this->db->save_queries = false;

		$select = 'donor_org.name as donor_org, donee_org.name as donee_org, donor_org.state as donor_state, donee_org.state as donee_state, donor_org.license as donor_license, donation.*,
		COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created) as date_status,
		YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) as year_status,
		MONTH(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) as month_status,
		CEIL(MONTH(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created))/3) as quarter_status,';

		$from = "FROM donation
		LEFT JOIN donation_items ON donation_items.donation_id = donation.id
		LEFT JOIN item ON item.id = donation_items.item_id
		LEFT JOIN org as donee_org ON donee_org.id = donation.donee_id
		LEFT JOIN org as donor_org ON donor_org.id = donation.donor_id";

		$date  = date(DB_DATE_FORMAT);

		$path  = dirname(__FILE__).'/../'.file::path('upload', "metrics.csv");

		@unlink($path);

		if ($items)
		{
			$select .= '
			donation_items.donor_qty as donor_qty,
			donation_items.donee_qty as donee_qty,
			donation_items.donee_qty * (donation_items.archived = 0) as accepted_qty,

			IF(donation_items.donor_qty is not null, 1, 0) as donor_count,
			IF(donation_items.donee_qty is not null, 1, 0) as donee_count,
			IF(donation_items.archived = 0, 1, 0) as accepted_count,

			donation_items.price * donation_items.donor_qty as donor_value,
			donation_items.price * donation_items.donee_qty as donee_value,
			donation_items.price * donation_items.donee_qty * (donation_items.archived = 0) as accepted_value,

			donation_items.exp_date,
			donation_items.price,
			item.id as item_id,
			item.name as item_name,
			item.description as item_desc,
			item.type,
			item.mfg,
			item.upc,
			item.price as current_price';

			$group = ''; //'donation_items.id';
			$name  = "All Items $items";
			$year  = "AND YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) = '$items'";
		}
		else
		{
			$select .= '
			IFNULL(SUM(donation_items.donor_qty), "") as donor_qty,
			IFNULL(SUM(donation_items.donee_qty), "") as donee_qty,
			IFNULL(SUM(donation_items.donee_qty * (donation_items.archived = 0)), "") as accepted_qty,

			SUM(IF(donation_items.donor_qty is not null, 1, 0)) as donor_count,
			SUM(IF(donation_items.donee_qty is not null, 1, 0)) as donee_count,
			SUM(IF(donation_items.archived = 0, 1, 0)) as accepted_count,

			IFNULL(SUM(donation_items.price * donation_items.donor_qty), "") as donor_value,
			IFNULL(SUM(donation_items.price * donation_items.donee_qty), "") as donee_value,
			IFNULL(SUM(donation_items.price * donation_items.donee_qty * (donation_items.archived = 0)), "") as accepted_value,

			donor_org.street as donor_street,
			donor_org.city as donor_city,
			donor_org.zipcode as donor_zip,
			donee_org.street as donee_street,
			donee_org.city as donee_city,
			donee_org.zipcode as donee_zip';
			$group = "GROUP BY donation.id";
			$name  = 'Donations';
			$year  = '';
		}

		$fields = implode("','", $this->db->query("SELECT $select $from LIMIT 1")->list_fields());

		$this->db->query
		(
			//UNION ALL is stackoverflow suggestion for including column names within an outfile
			//GROUP BY $group ORDER BY donation.id DESC
			"SELECT '$fields' UNION ALL
			(
				SELECT $select
				INTO OUTFILE '$path' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n'
				$from
				WHERE donation.archived = 0
				AND (donation_items.id > 0 OR donation.date_received > 0 OR donation.date_shipped > 0)
				$year
				$group
			)"
		);



		//ignore_user_abort(false);
		ini_set('output_buffering', 0);
		ini_set('zlib.output_compression', 0);

		$chunk = 1 * 1024 * 1024; // bytes per chunk (1 MB)

		$fh = fopen($path, "rb");

		if ($fh === false) {
		    echo "Unable open file";
				echo mysql_error();
		}

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . "SIRUM $name $date.csv" . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($path));

		// Repeat reading until EOF
		while (!feof($fh)) {
		    echo fread($fh, $chunk);
		    ob_flush();  // flush output
		    flush();
		}

		/* This was too slow 5+ mins and often failed with memory exhausted errors
		$csv = @file_get_contents($path);

		if ( ! $csv) echo mysql_error();

		$this->load->helper('download');

		force_download("SIRUM $name $date.csv", $csv);
		*/
	}

/**
| -------------------------------------------------------------------------
|  Swap
| -------------------------------------------------------------------------
|
|
*/

	function swap($user_id)
	{
		// can't do normal admin login since admin pages
		// need to be blocked to an admin in user mode
		// for this case we use the org::permit feature
		// within user::login
		data::get('admin_id') or user::login($org_id, 'admin');

		$user = user::find($user_id);

		$update = array
		(
			'user_id'		=> $user->user_id,
			'org_id'		=> $user->org_id,
			'admin_id'		=> $this->input->get('to') ? '' : data::get('user_id'),
			'org_name'		=> $user->org_name,
			'user_name'		=> $user->user_name,
			'date_donor' 	=> $user->date_donor,
			'date_donee'	=> $user->date_donee,
			'state'	      => $user->state,
			'license' 		=> $user->license
		);

		data::set($update);

		to::info(['Profile', "switched to $user->org_name"], 'to_default', 'donations');
	}
}  //END OF CLASS

/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
