<?=

	form::open('', ['class' => 'togglecontainer', 'style' => 'white-space:nowrap'])
	         ->upload('upload', 'Import', 'floatright avia-color-theme-color-subtle',['hidden' => true, 'onchange' => 'this.form.submit()']),

	html::label(form::dropdown("donor_id", '0', $users, 'tall wide'), 'From')

	->label(form::dropdown("donee_id", '0', $users, 'tall wide'), 'To')

	->div('wide main_color')

		->div('floatleft', form::radio('label_type', '', $label_types))

		->div('floatright', form::submit('Create')),

	form::close();
