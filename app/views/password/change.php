<?=
	html::form('', ['class' => 'main_color'])

		->label(form::password('password', '', 'wide'), 'Old Password')

		->label(form::password('new_password', '', 'wide'), 'New Password')

		->label(form::password('confirm_password', '', 'wide'), 'Confirm')

		->div('wide')

			->link('account/user', 'Back to Edit User')

			->submit('Update', 'avia-size-medium floatright');