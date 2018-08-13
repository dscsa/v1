<?php
class Password_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Question
| -------------------------------------------------------------------------
|
| Ask the security question as an extra security measure
| for authenticating a user.  After authentication, user can
| be redirected anywhere. Use salt rather than user_id so someone
| cannot easily iterate over questions until they find one they know
|
|  @param int user_id, permission user id
|
*/
	function _question($email)
	{
		$user = user::search(compact('email'));

		$user->question = 'No email was provided';

		$v = ['user' => $user, 'error' => $user->question];

		if (count($user) == 1)
		{
			$v = ['user' => $user[0], 'error' => ''];

			if (strtolower($v['user']->answer) == strtolower(data::post('answer')))
			{
				return true;
			}

			if (data::post('answer'))
			{
				$v['error'] =  text::get('question_attempt');
			}
		}

		view::part("common/header", ['title' => 'Password']);
		view::part('password/question', $v);
	}


/**
| -------------------------------------------------------------------------
| Reset
| -------------------------------------------------------------------------
|
| Link to page appears on login page.  If user forget password they
| must enter their email or login and correctly answer the security question
| associated with the account.  If successful, SIRUM will reset their password
| and email the user a random password in the secret question function,
| asking them to change when they login.
|
*/
	function reset($email = '')
	{
		$email = urldecode($email);

		if(self::_question($email))
		{
			$password = secure::password();

			$user = user::find(compact('email'));

			user::password($user->user_id, $password['hash']);

			user::email($user->user_id, 'email_password', [$password['text']]);

			to::info("A new password was emailed to $user->email", '', "login?email=$email");
		}
	}

/**
| -------------------------------------------------------------------------
|  Change
| -------------------------------------------------------------------------
|
| Allows user to change password when logged in on the edit user page
| User must enter old password, new password and confirm the new password
|
| @param int user_id, permission user id
|
*/
	function change()
	{
		user::login($org_id);

		if(valid::form())
		{
			user::password(data::get('user_id'));

			to::info(['password', 'changed'], 'to_default', 'account/users');
		}

		view::full('account', 'change password');
	}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/