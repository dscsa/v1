<?=
	html::form('', ['class' => 'main_color'], ['user.email' => $user->email])

		->label($user->input('user_name', 'tall wide'), 'Name')

		->label($user->input('email', 'tall wide'), 'Email')

		->tag('span', 'Question')

		->add
		(
			$user->dropdown('question', defaults::questions(), 'tall wide').

			$user->input('answer', 'tall wide')
		)

		->div('wide')

			->link('password/change', 'change password')

			->submit('Update', 'avia-size-medium floatright');