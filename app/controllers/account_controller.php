<?php
class Account_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Index
| -------------------------------------------------------------------------
|
| Displays org's info.  Allowing user to edit info, change their security question,
| or password.  If a group admin allows access to the create, show, and edit user
| functionality of that organization.
|
| If no user_id is passed to sirumMode->profile(), it fetches the current user's
| Group Admin's profile information. Therefore, for personal profile, need to pass
| perm_user_id' explicitly.
|
*/

	function index()
	{
		user::login($org_id);

		if (valid::submit('update') AND valid::form())
		{
			if ($path = data::post('upload'))
			{
				rename($path, file::path('images', "org/$org_id.jpg"));
			}

			org::update($org_id);

			to::info(['Saved!', 'Your organization was updated']);
		}

		$html = [
			'/\n/'          => '<br>',        //HTML linebreaks
			'/\*([^*]*)\*/' => '<b>$1</b>',   //Astericks -> Bold
			'/\[\]/'        => '&#x25a2;'     //[] become checkboxes
		];

		$org = org::find($org_id);

		if ( (int) $org->date_donee)
		{
			$html = ['/^(.+)(\n|$)/' => '<h3>$1</h3>'] + $html; //First line become title
		}

		$org->html_instructions = preg_replace(array_keys($html), array_values($html), $org->instructions);

		$v = [
			'editable'	=> data::post('button'),
			'org'       => $org
		];

		view::full('account', 'organization profile', $v);
	}

 /**
  | -------------------------------------------------------------------------
  |  Users
  | -------------------------------------------------------------------------
  |
  | Function allows group admins who registered through our site (and represent
  | a specific organization) to show all sub-users of that organization.  The group
  | admin can then choose to delete any of those sub users or edit their profiles
  |
  |  Accessible to group admins on the profile main page
  |
  */

	function users()
	{
		user::login($org_id);

		$v['results'] = user::search(compact('org_id'));

		view::full('account', 'authorized users', $v);
	}

 /**
  | -------------------------------------------------------------------------
  |  User
  | -------------------------------------------------------------------------
  |
  | Group admins can create, update, and delete member users for the organization.
  | Users are redirected when trying to use funcitonality outside of their permission.
  |
  | Org admins supply the name and email of the new user. A random password is
  | generated and sent to the subusers email address - it is then up to the user
  | to set her security question and modify her profile.
  |
  */

	function user()
	{
		user::login($org_id);

		$user_id = data::get('user_id');

		if (valid::form())
		{
			user::update($user_id, $org_id);

			to::info(['User', 'updated'], 'to_default', 'account/users');
		}

		view::full('account', 'edit user', ['user' => user::find($user_id)]);

	}

/**
| -------------------------------------------------------------------------
|  Authorize
| -------------------------------------------------------------------------
|
| Group admins can create, update, and delete member users for the organization.
| Users are redirected when trying to use funcitonality outside of their permission.
|
| Org admins supply the name and email of the new user. A random password is
| generated and sent to the subusers email address - it is then up to the user
| to set her security question and modify her profile.
|
*/
	function authorize()
	{
		user::login($org_id);

		if (valid::form())
		{
			$password = secure::password();

			$user_id = user::create($org_id, $password['hash']);

			user::email($user_id, 'email_welcome',[data::get('org_name').br(1)."Password $password[text]"]);

			to::info(['User created!', 'A welcome email was sent with a temporary password'], 'to_default', 'account/users');
		}

		view::full('account', 'authorize user');
	}


	/**
  | -------------------------------------------------------------------------
  |  Donors
  | -------------------------------------------------------------------------
  |
  | Allow donees to approve specific donors.  If not approved by a donee
  | that donor will not see the donee in the list when making a donation.
  |
  */

	function donors()
	{
		user::login($org_id, 'donee');

		if (valid::submit('Save Selection'))
		{
			org::_update(['approved' => ';'.implode(';', data::post('approve', [])).';'], $org_id);
		}

		$approved = explode(';', org::find($org_id)->approved);

		$search = ['date_donor >' => 0, 'org_id !=' => $org_id];

		if ($org_id != 1187) //charitable returns
		 $search['state'] = data::get('state');

		$per_page = result::$per_page;

 		result::$per_page = 9999;

 		$donors = org::search($search);

 		result::$per_page = $per_page;

		foreach($donors as $key => $donor)
		{
			$donors[$key]->approve = in_array($donor->org_id, $approved);
		}

		view::full('account', 'approved donors', ['donors' => $donors]);
	}

}

/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
