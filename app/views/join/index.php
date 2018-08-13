<?=

	//Fixed width because form is responsive and the columns were getting prematurely stacked on small screens
	html::form("", ['class' => 'main_color  modern-quote container', 'target' => '_self', 'style' => 'width:950px; margin:30px auto 0 75px'])

		->div('floatleft', '', ['style' => 'margin-right:50px'])

			->h3('Contact Info', 'av-special-heading-tag')

			->label(form::input('user_name', '', 'tall wide'), 'Name')

			->label(form::input('email', '', 'tall wide'), 'Email')

			->label(form::password('new_password', '', 'tall wide'), 'Password')

			->label(form::password('confirm_password', '', 'tall wide'), 'Confirm password')

			->div('label')

				->tag('span', 'Security question')

				->add(form::dropdown('question', '', defaults::questions(), "tall wide", ['style' => 'width:120px'])

				->input('answer', '', 'tall wide', ['placeholder' => 'Answer to security question']))

		->end(2)

		->div('floatleft')

			->h3(ucfirst($register).' Info', 'av-special-heading-tag')

			->label(form::input('org_name', '', 'tall wide'), 'Facility')

			->label(form::dropdown('license', '', $licenses, "tall wide"), ucfirst($register).' Type')

			->label(form::input('phone', '', 'tall wide'), 'Phone')

			->label(form::input('street', '', 'tall wide',  ['placeholder' => 'Street']), 'Address')

			->div('label')

				->div('inline-block', form::input('city', '', 'tall', ['placeholder' => 'City']))

				->div('inline-block', form::input('state', '', 'tall narrow', ['placeholder' => 'State']))

				->div('inline-block', form::input('zipcode', '', 'tall narrow', ['placeholder' => 'Zip']))

			->add(view::part('join/update', ['register' => $register, 'to' => 'join'], true));
