<?php

/**
| -------------------------------------------------------------------------
|  Password
| -------------------------------------------------------------------------
|
*/
	$new_password = array
	(
		array
		(
			'field' => 'new_password',
			'label' => 'New password',
			'rules' => 'required|trim|new_password'
		),
		array
		(
			'field' => 'confirm_password',
			'label' => 'Password confirmation',
			'rules' => 'required|trim|matches[new_password]'
		)
	);

	$old_password = array
	(
		array
		(
			'field' => 'password',
			'label' => 'old password',
			'rules' => 'required|trim|old_password'
		)
	);


/**
| -------------------------------------------------------------------------
|  Org
| -------------------------------------------------------------------------
|
*/
	$org = array
	(
		array
		(
			'field' => 'upload',
			'label' => 'Image',
			'rules' => 'image[jpg]'
		),
		array
		(
			'field' => 'org_name',
			'label' => 'Organization',
			'rules' => 'required|trim|ucwords'
		),
		array
		(
			'field' => 'license',
			'label' => 'Type',
			'rules' => 'required|trim'
		),
		array
		(
			'field' => 'phone',
			'label' => 'Phone',
			'rules' => 'required|trim|phone'
		),
		array
		(
			'field' => 'street',
			'label' => 'Address',
			'rules' => 'required|trim|address|strtolower|ucwords'
		),
		array
		(
			'field' => 'city',
			'label' => 'City',
			'rules' => 'required|trim|alpha_space|strtolower|ucwords'
		),
		array
		(
			'field' => 'state',
			'label' => 'State',
			'rules' => 'required|trim|alpha|exact_length[2]|strtoupper'
		),
		array
		(
			'field' => 'zipcode',
			'label' => 'Zip',
			'rules' => 'required|trim|is_natural_no_zero|exact_length[5]'
		),
		array
		(
			'field' => 'description',
			'label' => 'Description',
			'rules' => 'trim'
		)
	);

/**
| -------------------------------------------------------------------------
|  User
| -------------------------------------------------------------------------
|
*/
	$user = array
	(
		array
		(
			'field' => 'user_name',
			'label' => 'Name',
			'rules' => 'required|trim|alpha_space|strtolower|ucwords'
		),
		array
		(
			'field' => 'email',
			'label' => 'Email',
			'rules' => 'required|trim|valid_email|is_unique[user.email]'
		)
	);

/**
| -------------------------------------------------------------------------
|  Question
| -------------------------------------------------------------------------
|
*/
	$question = array
	(
		array
		(
			'field' => 'question',
			'label' => 'Question',
			'rules' => 'required|trim'
		),
		array
		(
			'field' => 'answer',
			'label' => 'Answer',
			'rules' => 'required|trim'
		)
	);

/**
| -------------------------------------------------------------------------
|  Agreement
| -------------------------------------------------------------------------
|
*/
	$agreements = array
	(
		array
		(
			'field' => 'agreements',
			'label' => 'Checking the box below to agree to our terms',
			'rules' => 'required'
		),
		array
		(
			'field' => 'agreements[date_donor]',
			'label' => '',
			'rules' => ''
		),
		array
		(
			'field' => 'agreements[date_donee]',
			'label' => '',
			'rules' => ''
		)
	);

/**
| -------------------------------------------------------------------------
|  Modify Request Validation - Helper Function
| -------------------------------------------------------------------------
|
| Callback ensures that modified information still has a valid quantity
| and future expiration date
|
*/
	$config['formulary/update'] = array
	(
		array
		(
			'field' => 'quantity',
			'label' => 'Amount',
			'rules' => 'required|trim|is_natural_no_zero'
		),
		array
		(
			'field' => 'minimum_amount',
			'label' => 'Minimum',
			'rules' => 'trim|is_natural_no_zero'
		),
		array
		(
			'field' => 'date',
			'label' => 'Expiration',
			'rules' => 'trim|date'
		)
	);

/**
| -------------------------------------------------------------------------
|  Change Password Validation - Helper Function
| -------------------------------------------------------------------------
|
| Standard password validation.  Requires alpha_dash
| which may incompatible with current generate password
| function which is why CSL is changing it to use codeigniters
| random string functionality
|
*/
	$config['shipping/pickup'] = array
	(
		array
		(
			'field' => 'date',
			'label' => 'pickup date',
			'rules' => 'required'
		),
		array
		(
			'field' => 'start',
			'label' => 'window',
			'rules' => 'required'
		),
		array
		(
			'field' => 'location',
			'label' => 'location',
			'rules' => ''
		)
	);

/**
| -------------------------------------------------------------------------
|  Login Validation - Helper Function
| -------------------------------------------------------------------------
|
| Validation rules to check responses during normal login and
| remember fields that the user inputted if their is a validation
| error after submitting.
|
*/

	$config['login/index'] = array
	(
		array
		(
			'field' => 'email',
			'label' => 'Email',
			'rules' => 'required|trim'
		),
		array
		(
			'field' => 'password',
			'label' => 'Password',
			'rules' => 'required|trim'
		)
	);

	$config['join/index'] = array_merge($org, $user, $new_password, $question, $agreements);

	$config['join/update'] = $agreements;

	$config['account/authorize'] = $user;

	$config['account/user'] = [$user[0], str_replace('required|', '', $user[1])];

	$config['password/change'] = array_merge($old_password, $new_password);

	$config['account/index'] = array_merge($org);


/**
| -------------------------------------------------------------------------
|  Add Inventory Item
| -------------------------------------------------------------------------
|
|
*/
	$config['inventory/add']  = array();

	$config['inventory/edit'] = array
	(
		array
		(
			'field' => 'increment',
			'label' => 'quantity',
			'rules' => 'integer'
		)
	);

/**
| -------------------------------------------------------------------------
|  Request Item
| -------------------------------------------------------------------------
|
|
*/
	$config['formulary/add'] = array
	(
		array
		(
			'field' => 'quantities[]',
			'label' => 'Quantity',
			'rules' => 'quantity'
		)
	);

/**
| -------------------------------------------------------------------------
|  Confirm Donation Items
| -------------------------------------------------------------------------
|
|
*/
	$config['donations/confirm'] = array
	(
		array
		(
			'field' => 'donation_id',
			'label' => 'Shipping label',
			'rules' => 'required'
		)
	);

/**
| -------------------------------------------------------------------------
|  Process Donations
| -------------------------------------------------------------------------
|
|
*/
	$config['donations/about'] = array
	(
		array
		(
			'field' => 'donation_type',
			'label' => 'donation_type',
			'rules' => 'required'
		),
		array
		(
			'field' => 'upload',
			'label' => 'File',
			'rules' => 'upload[txt, csv]'
		),
		array
		(
			'field' => 'donor_qty[]',
			'label' => 'quantity',
			'rules' => 'quantity'
		),
		array
		(
			'field' => 'donee_qty[]',
			'label' => 'donee quantity',
			'rules' => 'quantity'
		),
		array
		(
			'field' => 'add_to_inv[]',
			'label' => 'Add to Inventory',
			'rules' => ''
		)
	);

/**
| -------------------------------------------------------------------------
|  Create Label
| -------------------------------------------------------------------------
|
|
*/
	$config['admin/index'] = array
	(
		array
		(
			'field' => 'donor_id',
			'label' => 'from field',
			'rules' => 'required'
		),
		array
		(
			'field' => 'donee_id',
			'label' => 'to field',
			'rules' => 'required|approved'
		),
		array
		(
			'field' => 'label_type',
			'label' => 'label type',
			'rules' => 'required'
		)
	);


	$config['admin/items'] = array
	(
		array
		(
			'field' => 'product',
			'label' => 'Update NDCs',
			'rules' => 'upload[txt]'
		),
		array
		(
			'field' => 'nadac',
			'label' => 'Update Prices',
			'rules' => 'upload[csv]'
		),
		array
		(
			'field' => 'transactions',
			'label' => 'Import v2 Transactions',
			'rules' => 'upload[csv]'
		)
	);



	$config['inventory/index'] = array
	(
		array
		(
			'field' => 'upload',
			'label' => 'File',
			'rules' => 'upload[txt, csv]'
		)
	);

?>
