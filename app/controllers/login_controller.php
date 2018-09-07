<?php
class Login_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
|  Index (Login/sign_in)
| -------------------------------------------------------------------------
|
| Function display login form for the user to sign_in to the site
| Usually accessed by the sign_in link on top right of site but
| is also displayed when a user is not logged in tries to access
| any restricted page (except site admin pages which redirect to
| admin login).
|
| If javascript is enabled, using the sign in link will show
| an embeded page (one with out headers or footers) so that it can be
| shown in a colorbox lightbox.  Otherwise the site will not be embeded
| and the full page with headers and footers will be displayed.
|
| Any redirects to this page have the original uri_string appeneded
| this searches for the last segment of the login (embed = FALSE) and
| upon successful login redirects user to original site
|
| @param bool embed, default is false
|
*/

	function index()
	{
		$success = session::create(trim(data::post('email')), trim(data::post('password')));

		if($success === true)
		{
			return to::info([], 'to_disclaimer', data::get('admin') ? "admin" : "donations", true);
		}

		view::part("common/header", ['title' => 'Login']);
		$this->output->append_output(to::sent());
		view::part('login/index', ['error' => $success]);
	}

/**
| -------------------------------------------------------------------------
|  Sign Out
| -------------------------------------------------------------------------
|
| Destroys session of user with a confirmation message
| toplinks are changed to signed out links for navigating
| on the about sirum pages
|
*/

	function sign_out()
	{
		$this->session->sess_destroy();

		// make use of the http referrer functionality in to::error
		// to remember the page before this one, so if user logs in
		// again they will be taken back to the same page they left
		to::alert(['You successfully logged out!']);
	}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/