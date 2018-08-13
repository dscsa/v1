<?php
class session extends MY_Model
{

/**
| -------------------------------------------------------------------------
|  Create
| -------------------------------------------------------------------------
|
| Creates a session for a user from login or account creation. Store session
| data using CI's session library.
|
| @return bool error, true if error & false if valid
|
*/

	function create($email, $password)
	{
		if ( ! $email or ! $password)
		{
			return false;
		}

		$user = user::find(compact('email'));

		if ( ! count($user))
		{
			log::error("Unknown User $email");

			return text::get('login_unknown');
		}

		if ( ! session::password($password, $user))
		{
			log::error("Incorrect password $email");

			return text::get('login_attempt', [urlencode($email)]);
		}

		$insert = array
		(
			'user_id'		  => $user->user_id,
			'org_id'		  => $user->org_id,
			'org_name'		=> $user->org_name,
			'user_name'		=> $user->user_name,
			'date_donor' 	=> $user->date_donor,
			'date_donee'	=> $user->date_donee,
			'admin' 		  => $user->admin,
			'license' 		=> $user->license,
			'state' 		  => $user->state,
			'environment'	=> ENVIRONMENT
		);

		data::set($insert);

		$update = array
		(
			'last_login' => $user->current_login,
			'current_login' => gmdate('c')
		);

		self::update($update, $user->user_id, 'user');

		return true;
	}

	function password($password, $user = '')
	{
		$user = $user ?: user::find(data::get('user_id'));

		return $user->password === secure::hash($password, $user->salt);
	}


}  // END OF CLASS
