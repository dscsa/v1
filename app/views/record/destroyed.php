<?=
	html::div('togglecontainer')

		->toggler('', 'top:5px')->toggled($record->form()),

	$record->none('No items matching that criteria have been destroyed');