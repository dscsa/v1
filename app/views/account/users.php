<?=
	$results->fields
	(
		array('Member', 'user_name'),
		array('Email', 'email'),
		array('Created', 'created', 'date(created)'),
		array('Logged In', 'current_login', 'date(current_login)'),
		array(html::div('main_color')->button("account/authorize?iframe=true", 'Add User', 'avia-color-theme-color', ['rel' => 'lightbox']), '', 'edit_user()')
	);