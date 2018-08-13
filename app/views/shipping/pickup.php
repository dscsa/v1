<?=
	form::open(),

	html::div('', '', ['style' => 'margin-top:-30px'])

		->div('floatleft')

			->label(form::dropdown('date', '', $dates, ''), 'Pickup*')

			->label(form::dropdown('start', '', $options, ''), 'Window*')

			->label(form::input('location', '', '', ['placeholder' => 'Med Room, Nursing Station, etc']), 'Location')

		->end()

	->div('floatright', '', ['style' => 'margin-top:15px;'])

		->add(form::submit('Cancel Pickup', 'main_color avia-color-theme-color-subtle', ['style' => 'z-index:3']))

		->div('main_color inline-block', form::submit('Schedule', '', ['style' => 'z-index:3'])),

	form::close();