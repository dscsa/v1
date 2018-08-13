<?php
class org extends MY_Model
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
		  'org'	=> array('*, name as org_name, id as org_id, description as org_description, IF(date_donor > 0, IF(date_donee > 0, "Both", "Donor"), IF(date_donee > 0, "Donee", "Neither")) as registered'),
		  'user'	=> array('id as user_id, name as user_name, email, question, answer, current_login, last_login, ', 'org_id = org.id')
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
		// Can't just put this in the select cause because
		// we want to make sure we have the user_name and
		// any other user info associated with the last user
		// if no logins yet, then org will not appear so need is null too
		//$this->db->having('(last_login = max(last_login) OR max(last_login) is null)');
		//was not working with SCVMC which had three users but was returning 0 rows
		$this->db->join('user as last', 'last.org_id = org.id AND user.last_login < last.last_login', 'left');
		$this->db->where('last.id is null');

		parent::_make_where($where);
	}


/**
| -------------------------------------------------------------------------
|  Create
| -------------------------------------------------------------------------
|
| Create new organization and a group admin for it, add join credits, geo
| location, and default preferences.  Logs user in and then returns org_id.
|
|
*/
	function create()
	{
		$org = self::_org();

		$org += api::geolocate
		(
			"$org[street], $org[city], $org[state] $org[zipcode]"
		);

		$org['instructions'] = isset($org['date_donee'])
			? "Fax this sheet & donation record to (888) 858-8172"
			: "*2. PICKUP LOCATION*
Front Office            Nursing Station            DON Office            Other __________________________________

*NAME* _________________________  *TITLE* ____________________  *CELL PHONE* (       ) _____ - ______
*SIGNATURE* ______________________________   *DATE* __________________  *TOTAL # OF PAGES* ____";

		$org_id = self::_create($org);

		user::create($org_id);

		session::create(data::post('email'), data::post('new_password'));

		return $org_id;
	}


/**
| -------------------------------------------------------------------------
|  Update
| -------------------------------------------------------------------------
|
| Update organization and its geo location, and default preferences.
|
*/
	function update($org_id)
	{
		self::_update(self::_org(), $org_id);
	}

/**
| -------------------------------------------------------------------------
|  _Org
| -------------------------------------------------------------------------
|
| Get org data from post array for use in create and update
|
*/
	function _org()
	{
		return data::post('agreements', []) +
		[
			'license' 		=> data::post('license'),
			'zipcode'		=> data::post('zipcode'),
			'name'			=> data::post('org_name'),
			'phone'			=> data::post('phone'),
			'street'		   => data::post('street'),
			'city'			=> data::post('city'),
			'state'			=> data::post('state'),
			'description'	=> data::post('description'),
			'instructions'	=> data::post('instructions')
		];
	}
	
	function pickup($org_id, $start = '', $date = '', $location = '')
	{
		$org = org::find($org_id);
		
		$pickup = fedex::org_pickup($org, $start, $date, $location);

		if ($pickup['error']) {
			$msg = log::error("Pickup not scheduled for $org->org_name on $date: ".print_r($pickup['error'], true));
			admin::email('Pickup NOT scheduled', $msg);
			echo $msg;
		}
		else
		{
			$msg = log::info("Pickup Scheduled for $org->org_name on $date ".print_r($pickup['success'], true));
			admin::email('Pickup scheduled', $msg);
			echo $msg;
		}
	}
	
	


		
/**
| -------------------------------------------------------------------------
| Permit
| -------------------------------------------------------------------------
|
| Check if the current user has permission to edit the supplied org
| and if the org has registered for donor or donee functionality
|
| @param int user_id, options
| @return bool
|
*/
	function permit($org_id)
	{
		return ! $org_id or data::get('org_id') === $org_id;
	}

/**
| -------------------------------------------------------------------------
|  Admin
| -------------------------------------------------------------------------
|
| Checks if logged in user is a site admin and undoes any swapped users
|
*/
	function admin()
	{
		// if admin switches to a user then clicks the browser's back button
		// and then tries to navigate around in the admin view, we want to let
		// them and just undo the user session data behind the scenes.
		if ($admin_id = data::get('admin_id'))
		{
			to::url("admin/swap/$admin_id", true);
		}

		return data::get('admin');
	}

/**
| -------------------------------------------------------------------------
|  Msg
| -------------------------------------------------------------------------
|
| Sends a message to all users within an organization. Counterpart is user::msg
|
*/

	function msg($org_id, $message, $donation_id)
	{
		$users = user::search(compact('org_id'));

		foreach ($users as $user)
		{
			user::msg($user->user_id, $message, $donation_id);
		}
	}

/**
| -------------------------------------------------------------------------
|  Email
| -------------------------------------------------------------------------
|
| Sends an html email to all users within an organization.
|
| @param $org_id, int
| @param $email, array(subject, body)
| @param $data, optional array()
| @param $attachments, optional array()
*/

	function email($org_id, $email, $data = array(), $attachments = array())
	{
		$users = user::search(compact('org_id'));

		foreach ($users as $user)
		{
			user::email($user->user_id, $email, $data, $attachments);
		}
	}

}  // END OF CLASS
