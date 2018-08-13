<?=

	form::open('', ['class' => 'togglecontainer'])

	->input('tracking_number', '', "tall wide inline-block", ['tabindex' => 2, 'placeholder' => 'Tracking Number']),

	html::toggler(form::submit('Search', 'avia-size-medium avia-color-grey'))

		 ->toggled($results->form()),

	$results->none("No donations match this criteria."),

	form::close();