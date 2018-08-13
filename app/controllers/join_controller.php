<?php
class Join_controller extends MY_Controller
{
	function agreement($register = 'donor')
	{
		view::part("common/header", ['title' => 'SIRUM User Agreement & BAA']);
		view::part('join/agreement', ['register' => $register]);
	}

/**
| -------------------------------------------------------------------------
|  Index
| -------------------------------------------------------------------------
|
| 1st page/step of registration.  Reached by sign up link at top right of site
| Creates new user for someone not already logged in.
| Checks registration fields to see if user wants to registerto be a  donor and/or
| clinic which informs the agreements shown on the second page.  Sends email
| to new user welcoming them to SIRUM and summarizing their account.
|
| Asks for all information needed to create an account.  Checks for unique
| login and email address.  Runs FedEx validation on street address to make
| sure its valid.  Users who sign up this way are considered "group admins" meaning
| that they are the primary representaive of their organization (agreed to the contract)
| and can create and permit other user to access SIRUM on behalf of their organization
|
*/
	function index($register = 'donor')
	{
		if (valid::form())
		{
			$org_id = org::create();

			$address = data::post('street').'<br>'.nbs(14).data::post('city').', '.data::post('state').' '.data::post('zipcode');

			//org::email($org_id, 'email_welcome', [data::post('org_name').br(1).'Address '.$address], "doc/SIRUM - ".strtoupper($register)." USER AGREEMENT.pdf");

			//send donor info to recipient for approval
			if ('development' != ENVIRONMENT AND 'donor' == $register)
			{
				$approve_org = [data::post('org_name'), defaults::license(data::post('license')), data::post('user_name'), data::post('phone'), data::post('email'), $address];

				if ('testing' == ENVIRONMENT)
				{
					user::email(1, 'email_approval', $approve_org);
				}
				else if ('CA' == data::post('state'))
				{
					//Email Khanh.  Used to be 262 for Quelan
					user::email(784, 'email_approval', $approve_org);
				}
				else //if ('CO' == data::post('state'))
				{
					user::email(232, 'email_approval', $approve_org);
				}
			}

			to::info([], 'to_welcome', 'inventory', true);
		}

		if (data::post())
		{
			admin::email("Registration Failed");
		}

		$licenses = defaults::$register();
		unset($licenses['Oregon']);

		view::part("common/header", ['title' => 'Join']);
		view::part('join/index', compact('register', 'licenses'));
	}

/**
| -------------------------------------------------------------------------
|  Update
| -------------------------------------------------------------------------
|
| Page display either the donor or donee agreement or both
| This page is called upon when accessing a feature which the user
| has not agreed to the appropriate user agreement yet.
|
| @param array data,  display agreement if register_donor register_clinic isset
| @param string register, options
|
*/
	function update($register = 'donor')
	{
		user::login($org_id);

		if (valid::form())
		{
			org::update($org_id);  //org model parses post data

			data::set(data::post('agreements'));

			admin::email(data::get('org_name')." Updated $register user agreement", "", "", "doc/SIRUM - ".strtoupper($register)." USER AGREEMENT.pdf");

			to::info(['Registration', 'updated'], 'to_default', 'donations');
		}

		view::full('account', 'Update Agreement', ['register' => $register, 'to' => data::all('to', 'donations')]);
	}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
