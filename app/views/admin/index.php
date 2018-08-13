<?=
	form::open(),

	html::label(form::dropdown("donor_id", '0', $users, 'tall wide'), 'From')

	->label(form::dropdown("donee_id", '0', $users, 'tall wide'), 'To')

	->div('wide main_color')

		->div('floatleft', form::radio('label_type', '', $label_types))

		->div('floatright', form::submit('Create')),

	form::close();