<?=
	form::open()->dropdown('donation_id', '', $donations, "tall floatleft", ['style' => 'width:80%; margin-bottom:0px']),

	html::div('floatright', isset($items[0]) ? form::submit('Submit') : '')

		->div('togglecontainer')

		->toggler('', 'top:5px')->toggled($items->form(false))

		->add($items->none("Add items to your inventory in order to donate"))

		->div('red', 'Note: a zero quantity excludes the item from the donation.'),

	form::close();
