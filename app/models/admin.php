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
				$bcc = 'adam@sirum.org, victoria@sirum.org, george@sirum.org, omar@sirum.org, '.EMAIL.( $email ? ', '.SALESFORCE : '');
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

		foreach(is_array($attachments) ? $attachments : array($attachments) as $path)
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
	
				list($donor_name, $donee_name, $num_labels) = $data; //get variables, force user to use three columns in order

				$donor_obj = org::search(['org.name' => $donor_name]);
				if(count($donor_obj) == 0){
					return self::$bulk['alerts'][] = array_merge($data, ["Donor name doesn't match V1"]);	
				}
				$donee_obj = org::search(['org.name' => $donee_name]);
				if(count($donee_obj) == 0){
					return self::$bulk['alerts'][] = array_merge($data, ["Donee name doesn't match V1"]);	
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
					];
				} else {
					return self::$bulk['alerts'][] = array_merge($data, ["Recipient has not approved this donor."]);	
				}
			}
	}


}  // END OF CLASS
