<?php

class Shipping_controller extends MY_Controller
{

/**
| -------------------------------------------------------------------------
| Pickup
| -------------------------------------------------------------------------
|
| Uses FedEx API to create a courier dispatch if none exists or cancels a pending
| courier dispatch if one already exists for the donation. Will show button on donation
| status page that allows the donor to pay additional money in order for FedEx to
| come pickup the package.  Pickup occurs only during certain times during closeby
| business days which requires complex validation. This step is completely optional
| as the donor may already have pickups schedules or would rather drop off the
| package themselves
|
| @param int donation_id is required, in order to associate the courier pickup with at least
| one donation, although pickup could be for more than one package
| @pram boolean donor_id_frmadmin, options
*/

	function pickup($donation_id)
    {
		user::login($org_id, 'donor');

		$donation = donation::pickup
		(	$donation_id
		,	data::post('start')
		,	data::post('date')
		,	data::post('location')
		);

		$v = fedex::window($donation);

		if ($v['error'])
		{
			to::alert(['Pickup cannot be scheduled at this time', $v['error']]);
		}

		view::full('donations', 'Schedule Pickup', $v['success']);
	}

/**
| -------------------------------------------------------------------------
|  Label //TODO: Probably should do custom authentication here.
| -------------------------------------------------------------------------
|
| Page is displayed if donee clicks the ship manually button on the donation
| status page.  Asks the user to confirm that they want to ship manually.
|
| @param int donation_id,  transaction id of the donation to be ship manually
| @pram boolean download, options
| @pram int user_id, options
|
*/
	function label($donation_id, $download = FALSE)
	{
		$file = donation::label(donation::find($donation_id));

		$this->output->no_cache();

		to::url(file::path('label', $file));
	}

/**
| -------------------------------------------------------------------------
| Manifest //TODO: Probably should do custom authentication here.
| -------------------------------------------------------------------------
|
| Run in background to send donor an email with label and slip
| just in case they don't print it out while they are online.  We add
| delay just to make sure that the slip and label have sufficient time
| to be generated
|
| @param int donation_id,  transaction id of the donation to create a packing slip
| @pram boolean redirect, options
| @pram string from, options
*/
	function manifest($donation_id, $overwrite = true)
	{
		$file = donation::manifest($donation_id, $overwrite);

		$this->output->no_cache();

		$this->output->set_content_type('text/html');

		to::url(file::path('manifest', $file));
	}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
