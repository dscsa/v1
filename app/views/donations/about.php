<?=
	isset($message) ? $message : '',

	form::open('', [], ['donation_type' => $results[0]->donation_type]),

	html::alert(validation_errors())

	->div('twelve units togglecontainer', '', ['style' => 'white-space:nowrap'])

		->button("shipping/label/$donation_id?iframe=true", 'Label', 'floatright avia-color-theme-color-subtle', ['rel' => 'lightbox', 'style' => 'margin-left:2px'])

		->button("shipping/manifest/$donation_id?iframe=true", 'Print', 'floatright avia-color-theme-color-subtle', ['rel' => 'lightbox', 'style' => 'margin-left:2px'])

		->add(form::upload('upload', 'Import', 'floatright avia-color-theme-color-subtle', ['hidden' => true, 'onchange' => 'this.form.submit()']))

		->add(form::input('upc', '', "tall inline-block", ['style' => 'width:65%;', 'tabindex' => 3, valid::submit('Save Quantity') ? 'autofocus' : false => true, 'placeholder' => 'Add or filter items by name or ndc', 'id' => 'adddonation']))

		->toggler(form::submit('Search')),

	count($results) ? html::div('main_color floatright', form::submit('Save Quantity', ''))

		->add($results->status().'  |')

		->strong('Tracking ')

		->span($results->fedex().' | ', 'main_color')

		->strong($results->donation_type == "Donation"  ? ' To ' : ' From ')

		->span($results->partner().' | ', 'main_color')

		->strong($results->sum('quantity'))

		->add(" units from ")

		->strong($results->sum('count'))

		->add(" items worth ")

		->strong("$".$results->sum('value', 0))

		->add($results->id ? $results : $none) : $none,

		form::close();
