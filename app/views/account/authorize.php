<?=
	html::form('', ['class' => 'main_color'])

		->label(form::input('user_name', '', 'tall wide'), 'Name')

		->label(form::input('email', '', 'tall wide'), 'Email')

		->tag('span', 'Question')

		->add
		(
			form::dropdown('question', '', defaults::questions(), 'tall wide')->input('answer', '', 'tall wide', ['placeholder' => 'Answer'])
		)

		->submit('Create', 'avia-size-medium avia-color-grey');