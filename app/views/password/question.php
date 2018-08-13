<?=
	html::form('', ['class' => 'main_color'])

		->alert($error)

		->h3('Answer Your Question')

		->label(form::input('answer', '', '', ['placeholder' => 'Answer is not case sensitive']), html::strong("Question: $user->question"))

		->submit('Submit');