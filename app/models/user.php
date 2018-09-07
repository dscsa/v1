<?php
class user extends MY_Model
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
		$date = gmdate('Y-m-d H:i:s');

		$num_donations = "(SELECT SUM(COALESCE( date_shipped, date_received, date_verified, donation.created ) < '$date' AND COALESCE( date_shipped, date_received, date_verified, donation.created ) != 0) FROM donation WHERE org.id = donor_id) as num_donations";
		$num_pending   = "(SELECT SUM(COALESCE( date_shipped, date_received, date_verified, donation.created ) > '$date' OR COALESCE( date_shipped, date_received, date_verified, donation.created ) = 0) FROM donation WHERE org.id = donor_id) as num_pending";
		$last_donation = "(SELECT MAX(COALESCE( date_shipped, date_received, date_verified, donation.created )) FROM donation WHERE org.id = donor_id) as last_donation";

		$this->db->select("$num_donations, $num_pending, $last_donation", false);

		return array
		(
			'user'	  => array("*, id as user_id, name as user_name"),
			'org'		  => array('*, name as org_name, description as org_description', 'id = user.org_id')
			//'donation' => array("SUM(COALESCE( date_shipped, date_received, date_verified, donation.created ) < '$date' AND COALESCE( date_shipped, date_received, date_verified, donation.created ) != 0) as num_donations, SUM(COALESCE( date_shipped, date_received, date_verified, donation.created ) > '$date' OR COALESCE( date_shipped, date_received, date_verified, donation.created ) = 0) as num_pending, MAX(COALESCE( date_shipped, date_received, date_verified, donation.created )) as last_donation", 'donor_id = org.id') // join donee donations works but raises time for page load from 3.8 seconds to 25 seconds 'donor_id = org.id OR donation.donee_id = org.id'
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
			case 'email':
				$this->db->where($field,$value);
				break;

			default:
				self::_where($field, $value);
				break;
		}
	}

/**
| -------------------------------------------------------------------------
|  Create
| -------------------------------------------------------------------------
|
| Creates a temporary password for a new user if one is not provided
| Uses a reference to set user_id.  Extends the update function
|
*/
	function create($org_id, $hash = '')
	{
		self::update('', $org_id, 'create');

		$user_id = $this->db->insert_id();

		self::password($user_id, $hash);

		return $user_id;
	}

/**
| -------------------------------------------------------------------------
|  Update
| -------------------------------------------------------------------------
|
| Uses a reference to set the user_id on insert.
|
*/
	function update($user_id = '', $org_id = '', $action = 'update')
	{
		$user = array
		(
			'org_id' 	 => $org_id,
			'name'		 => data::post('user_name'),
			'email'		 => data::post('email'),
			'question' => data::post('question'),
			'answer'	 => data::post('answer'),
		);

		self::{"_$action"}($user, $user_id);
	}

/**
| -------------------------------------------------------------------------
|  Password
| -------------------------------------------------------------------------
|
*/
	function password($user_id, $hash = '')
	{
		self::_update($hash ?: secure::hash(data::post('new_password')), $user_id);
	}

/**
| -------------------------------------------------------------------------
|  Login
| -------------------------------------------------------------------------
|
| Checks if user is logged in and sets their org_id for use in controller
| Does user have session for the right environment and is user registered
| as the correct user type to access this functionality
|
| Is user registered for the appropriate user type (clinic or donor)?
| Does user have permission of their group admin for this functionality?
|
| @param string type_allowed_access, option donor, donee, all
| @param string permission_required, option one of the permissions listed in the permission table
|
*/

	function login( & $org_id = '', $permission = '')
	{
		if(data::get('environment') != ENVIRONMENT)
		{
			to::url('login', true);
		}

		if ($permission == 'admin')
		{
			if (org::admin())
			{
				$org_id = data::get('org_id');

				return true;
			}

			to::alert([site_url($this->uri->uri_string())], 'permission_admin', 'inventory');
		}

		//don't allow admins to look around in their own user's view without an explicit swap
		if (data::get('admin') and ! data::get('admin_id'))
		{
			to::alert([site_url($this->uri->uri_string())], 'permission_user', 'admin');
		}

		if($permission and ! (int) data::get("date_$permission"))
		{
			to::url("join/update/$permission", true);
		}

		// A user can do anything for their org but not for other orgs
		// users. Check to see if user has org admin perm over given user_id
		if (org::permit($org_id))
		{
			$org_id = data::get('org_id');

			return true;
		}

		to::alert([site_url($this->uri->uri_string())], 'permission_user', 'inventory');
	}

/**
| -------------------------------------------------------------------------
|  Email
| -------------------------------------------------------------------------
|
| Sends an html email to one user. Counterparts are org::email & admin::email
|
| @param $user_id, int
| @param $email, array(subject, body)
| @param $data, optional array()
| @param $attachments, optional array()
|
*/
	function email($user_id, $email, $data = array(), $attachments = array())
	{
		if ( ! $user_id) return;

		$user = user::find($user_id);

		$email = text::get($email, $data);

		$view = $this->load->view('common/email', ['body' => text::get($email[1], $data), 'user' => ucwords($user->user_name)], true);

		admin::email($email[0], $view, $user->email, $attachments);

		log::info("EMAIL - with subject '$email[0]' sent to $user->email");
	}

}  // END OF CLASS
