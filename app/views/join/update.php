<?=
	html::form()

			->div('main_color', '', ['style' => 'margin-left:-7px'])

				->add(form::error('agreements', '<div class="red">'))

				->add(form_checkbox(['name' => "agreements[date_$register]", 'value' => gmdate('c'), 'checked' => isset($_POST['agreements']["date_$register"])]))

				->strong()

					->h6("I have read and accept SIRUM's&nbsp;", 'inline-block')

					->link("join/agreement/$register?to=$to", ucwords($register).' Agreement') //file::load('doc', $register.'_agreement_hipaa.txt') //link('doc/SIRUM - '.strtoupper($register).' USER AGREEMENT.pdf', 'User Agreement'.nbs(2))

				->end()->br()

				->submit('Submit', 'avia-size-medium', ['style' => 'margin-left:255px']);
