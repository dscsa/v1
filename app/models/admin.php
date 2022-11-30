<?php
class admin extends MY_Model
{

/**
| -------------------------------------------------------------------------
|  Email
| -------------------------------------------------------------------------
|
| Send unformatted email to the admin. Counterparts are org::email & user::email
|
*/


  //Eventually this will be called in or replace the admin::email functionality for all email
  //First phase: only using it for individual donation emails
  //Connects to the comm-cal web app and (currently only for email) uses it for outbound communication
  //Event title = title of calendar event, only matters internally
  //Email subject, email address = obvious
  //email body = result of $this->load->view('common/email',...... and text::get('email_individual_donation_success',.... earlier  = full html of the email
  //filenames = the filenames of the documents to attach  W/O filetype at end . The comm-cal will ping donation::pullLabelBlob
  //Because of the way Google script web apps redirect, couldn't just post entire file blob, so we ping & it will call for the files in a moment
  //$error version of event_title, subject, and body -- these are the default error message, and subject and title, to use in case there's an error on GScript side
  function email_through_comm_cal($event_title, $email_subject, $email_address, $email_body, $filenames, $error_event_title, $error_email_subject,  $error_email_body)
  {
    $url = 'https://script.google.com/macros/s/AKfycbxGd4CIQHDTYuj2Jm0QxEJdL_Xzk1mHZHVNWOvl3sRVgZwjxZY/exec';

    //The comm-arr: first is an array, second is an object with the comm-obj properties
    $body = array(
      'blobs' => $filenames,
      'email' => $email_address,
      'message' => $email_body,
      'workHours' => false,
      'from' => 'hello@sirum.org',
      'subject' => $email_subject
    );

    $data = array(
      'title' => $event_title,
      'body' => json_encode(array($body)),
      'send_now' => True,  //because we're sending all the html of the email, it will get corrupted when saved to cal-event, so the webapp can send the html directly before saving the event - a little shortcut
      'password' => secure::key('commcal_key')
    );

    $response = self::sendPost($url, $data);

    if(strpos($response, 'error') !== false){
      $body['message'] = $error_email_body;
      $body['subject'] = $error_email_subject;
      $body['bcc'] = 'ERROR_TEAM'; //so we can use gscript/gsheet to store the emails to use for alerts here
      unset($body['blobs']);
      $data['title'] = $error_event_title;
      $data['body'] = json_encode(array($body));

      $response = self::sendPost($url, $data);
    }

    log::info("admin::email_through_comm_cal" . print_r([$url, $data, $response], true));
    admin::email("admin::email_through_comm_cal", print_r([$url, $data, $response], true));
  }

  function comm_cal_email($email_subject, $email_body = '(No Message)', $email_address = '', $filepaths = [])
  {
    log::info("admin::comm_cal_email start" . print_r([$email_subject, $email_body, $email_address, $filepaths], true));

    $url = 'https://script.google.com/macros/s/AKfycbxGd4CIQHDTYuj2Jm0QxEJdL_Xzk1mHZHVNWOvl3sRVgZwjxZY/exec';

    //Want to make public URL folder is the base path so
    //"/dscsa/sirum/url/label/D1364R1187T29162P_label.pdf" should be given as "label/D1364R1187T29162P_label.pdf"
    foreach($filepaths as $i => $filepath) {
      $api_key  = secure::key('cron');
      $filepaths[$i] = "https://donate.sirum.org/bkg/$api_key/admin/get_file/$filepath";
    }

    //The comm-arr: first is an array, second is an object with the comm-obj properties
    $body = [
      'blobs' => $filepaths,
      'email' => $email_address,
      'message' => $email_body,
      'workHours' => false,
      'from' => 'support@sirum.org',
      'subject' => $email_subject
    ];

    switch (ENVIRONMENT)
		{
			case 'testing':
        $body['subject'] = ENVIRONMENT.': '.$body['subject'];
				$body['bcc'] = 'adam@sirum.org';
				break;

			case 'development':
        $body['subject'] = ENVIRONMENT.': '.$body['subject'];
				$body['bcc'] = 'adam@sirum.org';
				break;

			case 'production':
				$body['bcc'] = 'archive@sirum.org, donations@sirum.org, adam@sirum.org, george@sirum.org '.( $email_address ? ', '.SALESFORCE : '');
				break;
		}

    $data = [
      'calendar_id' => 'c_94dou5i0ough2u60gir3ua3fpg@group.calendar.google.com',
      'title' => "v1 $email_address $body[subject]",
      'body' => json_encode([$body]),
      'send_now' => true,  //because we're sending all the html of the email, it will get corrupted when saved to cal-event, so the webapp can send the html directly before saving the event - a little shortcut
      'password' => secure::key('commcal_key')
    ];

    log::info("admin::comm_cal_email start" . print_r([$url, $data], true));

    $response = self::sendPost($url, $data);

    log::info("admin::comm_cal_email end" . print_r($response, true));

    return strpos($response, 'error') === false
      ? ['success' => $response, 'error' => null]
      : ['success' => null, 'error' => $response];
  }

  function sendPost($url, $data){
    $ch = curl_init($url);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT,10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //because gscript webapps are stupid about redirects
    curl_setopt($ch, CURLOPT_FAILONERROR,true);
    curl_setopt($ch, CURLOPT_ENCODING,"");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
  }

  //TODO make this safer with file path validation
  function get_file()
  {

    //Since file_path may have / in them (even when urlencoded) CI will interpret this as separate parameters
    $file_path = implode("/", func_get_args());
    $file_path = rtrim($file_path, "/"); //bkg adds a last parameter which creates a trailing /

    log::info("admin::get_file url-based file_path: " . $file_path);

    try{

      $file_contents = file_get_contents($file_path);
      echo base64_encode($file_contents);
      flush();
    } catch(Exception $e) {
      echo "Error:";
      echo json_encode($e);
      flush();
    }
  }

  //This is the email functionality that will soon be depracated. Currently in use for all emails not for individual donations
   function email($subject, $message = '', $email = '', $attachments = [])
   {
		$this->load->library('email', ['protocol' => 'sendmail', 'mailtype' => 'html']);

		//true clears attachments as well
		$this->email->clear(true);

		switch(ENVIRONMENT)
		{
			case 'testing':
				$from = [str_replace('@', '@test.', EMAIL), 'TEST SIRUM'];
				$bcc = 'adam@sirum.org';
				break;

			case 'development':
				$from = [str_replace('@', '@local.', EMAIL), 'LOCAL SIRUM'];
				$bcc = 'adam@sirum.org';
				break;

			case 'production':
				$from = [EMAIL, 'SIRUM'];
				$bcc = 'archive@sirum.org, donations@sirum.org, adam@sirum.org, george@sirum.org, '.EMAIL.( $email ? ', '.SALESFORCE : '');
				break;
		}

		if (is_array($message))
		{
			list($subject, $message) = text::get($subject, $message);
		}

		$message = str_replace('{email}', $email, $message);

		if ( ! $message)
		{
			$post = data::post() + ['agreements' => ''];
			$errs = $this->form_validation->_error_array;

			foreach($post as $key => $val)
			{
				//Censor sensitive data
				$input = ( ! $val OR $key != 'password' AND $key != 'confirm_password' AND $key != 'new_password') ? $val : '*removed*';

				//When Agreements is input than array with
				$post[$key] = @"$key: $input ".ifset($errs[$key]);
			}

			$message = implode('<br>', $post);
		}

    $attachments = is_array($attachments) ? $attachments : array($attachments);

		foreach($attachments as $path)
		{
			file::exists($path) ? $this->email->attach($path) : log::error("Email Attachment $path does not exist");
		}

		$this->email

			->message($message)

			->to($email)

			->bcc($bcc)

			->subject($subject)

			->from($from[0], $from[1])

			->send();

      self::comm_cal_email("v1 comm_cal_email to:$email $subject", $message, 'adam@sirum.org', $attachments); //Testing without a specified email for now
	}

/**
| -------------------------------------------------------------------------
| Digest
|-------------------------------------------------------------------------
|
| For now just, email log file to admin for review.  could incorporate more
| items such as signups/logins/# of donations etc.
|
| @param string password, options
|
*/

	function digest()
	{
		//Since sent at 1am need to send yesterdays logfile
		$date = date('Y-m-d', strtotime('yesterday'));

		if ($log = file::read('log', "log-$date.php"))
		{
			self::email("Log $date", str_replace("\n", br(2), $log));
		}
		else
		{
			log::info("CRON - no log $date file");
		}
	}

	static $bulk  =
	[
		'alerts' => [],
		'upload' => [],
	];

/**
| -------------------------------------------------------------------------
| Import
|-------------------------------------------------------------------------
| Reads the import csv for mass label creation
|
*/

	function import($data,$row)
	{
			if($row > 1){

				list($donor_name, $donee_name, $num_labels,$attn) = $data; //get variables, force user to use three columns in order

				$donor_name = str_replace("'","''",$donor_name);//need to escape single quotes so that the sql query can run
				$donee_name = str_replace("'","''",$donee_name);//same

				$donor_obj = org::search(["org.name LIKE '$donor_name'"]);
				if(count($donor_obj) == 0){
					return self::$bulk['alerts'][] = array_merge($data, ["Donor name doesn't match V1"]);
				}
                if(count($donor_obj) > 1){
					return self::$bulk['alerts'][] = array_merge($data, ["Donor name has multiple matches"]);
				}

				$donee_obj = org::search(["org.name LIKE '$donee_name'"]);

				if(count($donee_obj) == 0){
					return self::$bulk['alerts'][] = array_merge($data, ["Donee name doesn't match V1"]);
				}
                if(count($donee_obj) > 1){
					return self::$bulk['alerts'][] = array_merge($data, ["Donee name has multiple matches"]);
				}
				//require an attn field for bulk
				if(strlen($attn) == 0){
					return self::$bulk['alerts'][] = array_merge($data,["No ATTN field provided for mailing"]);
				}

				$donor_id = $donor_obj[0]->id;
				$donee_id = $donee_obj[0]->id;

				$donee_approved = $donee_obj[0]->approved;
				if(strpos($donee_approved, ';'.$donor_id.';') !== false){ //then they are approved
					self::$bulk['upload'][] =
					[
						'donor_id' => $donor_id,
						'donee_id' => $donee_id,
						'num_labels' => $num_labels,
						'attn_field' => $attn
					];
				} else {
					return self::$bulk['alerts'][] = array_merge($data, ["Recipient has not approved this donor."]);
				}
			}
	}

  function metrics($items = false)
	{
		set_time_limit(0);
		$this->output->enable_profiler(FALSE);
		$this->db->save_queries = false;

		$select = "
        donor_org.name as donor_org,
        donee_org.name as donee_org,
        donor_org.state as donor_state,
        donee_org.state as donee_state,
        donor_org.license as donor_license,
        donation.*,
		COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created) as date_status,
		YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) as year_status,
		MONTH(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) as month_status,
		CEIL(MONTH(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created))/3) as quarter_status,
        ROUND(SUM(donation_items.donee_qty/COALESCE(donation_items.qty_per_rx, item.qty_per_rx, 30))) as donated_rxs,
        ROUND(SUM(COALESCE(donation_items.accepted_qty, donation_items.donee_qty*COALESCE(donee_org.percent_accepted, 0.5))/COALESCE(donation_items.qty_per_rx, item.qty_per_rx, 30))) as accepted_rxs,
        ROUND(SUM(COALESCE(donation_items.dispensed_qty, donation_items.accepted_qty*COALESCE(donee_org.percent_dispensed, 0.5), donation_items.donee_qty*COALESCE(donee_org.percent_dispensed, 0.5)*COALESCE(donee_org.percent_accepted, 0.5))/COALESCE(donation_items.qty_per_rx, item.qty_per_rx, 30))) as dispensed_rxs,
        ROUND(SUM(COALESCE(donation_items.dispensed_qty, donation_items.accepted_qty*COALESCE(donee_org.percent_dispensed, 0.5), donation_items.donee_qty*COALESCE(donee_org.percent_dispensed, 0.5)*COALESCE(donee_org.percent_accepted, 0.5))/COALESCE(donation_items.qty_per_rx, item.qty_per_rx, 30)/COALESCE(donation_items.rxs_per_patient, 6.97))) as dispensed_patients,";

		$from = "FROM donation
		LEFT JOIN donation_items ON donation_items.donation_id = donation.id
		LEFT JOIN org as donee_org ON donee_org.id = donation.donee_id
		LEFT JOIN org as donor_org ON donor_org.id = donation.donor_id
        LEFT JOIN item ON item.id = donation_items.item_id";

		$date  = date(DB_DATE_FORMAT);

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
			$year  = '';
		}

		$fields = implode("','", $this->db->query("SELECT $select $from LIMIT 1")->list_fields());

    $file = $items ?: "Donations";
    $path = dirname(__FILE__).'/../'.file::path('upload', "$file.csv");

    @unlink($path);

		$this->db->query
		(
			//UNION ALL is stackoverflow suggestion for including column names within an outfile
			//GROUP BY $group ORDER BY donation.id DESC
			"SELECT '$fields' UNION ALL
			(
				SELECT $select
				INTO OUTFILE '$path' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n'
				$from
				WHERE (donation_items.id > 0 OR donation.date_received > 0 OR donation.date_shipped > 0)
				$year
				$group
			)"
		);
	}

  function drugs_by_donee_state($year) {

    log::info("CALLED drugs_by_donee_state $year");

    set_time_limit(0);
		$this->output->enable_profiler(FALSE);
		$this->db->save_queries = false;

    $path = dirname(__FILE__).'/../'.file::path('upload', "drugs_by_donee_state $year.csv");

    $query = "SELECT
      item.name,
      org.state,
      SUM(donor_qty),
      SUM(donee_qty),
      SUM(accepted_qty),
      SUM(donor_count),
      SUM(donee_count),
      SUM(accepted_count),
      SUM(donor_value),
      SUM(donee_value),
      SUM(accepted_value)
    INTO OUTFILE '$path' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n'
    FROM org
    JOIN (
       SELECT
          donee_id,
          item_id,
          IFNULL(SUM(donation_items.donor_qty), '') as donor_qty,
    			IFNULL(SUM(donation_items.donee_qty), '') as donee_qty,
    			IFNULL(SUM(donation_items.donee_qty * (donation_items.archived = 0)), '') as accepted_qty,

    			SUM(IF(donation_items.donor_qty is not null, 1, 0)) as donor_count,
    			SUM(IF(donation_items.donee_qty is not null, 1, 0)) as donee_count,
    			SUM(IF(donation_items.archived = 0, 1, 0)) as accepted_count,

    			IFNULL(SUM(donation_items.price * donation_items.donor_qty), '') as donor_value,
    			IFNULL(SUM(donation_items.price * donation_items.donee_qty), '') as donee_value,
    			IFNULL(SUM(donation_items.price * donation_items.donee_qty * (donation_items.archived = 0)), '') as accepted_value
       FROM donation_items
       LEFT JOIN donation ON donation.id = donation_items.donation_id
       WHERE YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) = '$year'
      GROUP BY donee_id, item_id
    ) as sum ON donee_id = org.id
    LEFT JOIN item ON item.id = item_id
    GROUP BY item.name, org.state";

    $query = "SELECT 'drug_name','donee_state','donor_qty','donee_qty','accepted_qty','donor_count','donee_count','accepted_count','donor_value','donee_value','accepted_value' UNION ALL ($query)";

    @unlink($path);

    log::info("START drugs_by_donee_state $year - $query");

		$this->db->query($query);

    log::info("STOP drugs_by_donee_state $year - $query");
  }
}  // END OF CLASS
