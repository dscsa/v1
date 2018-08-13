<?=

	html::div('togglecontainer')

		->toggler('', 'top:5px')->toggled($record->form()),

	$record->none('Please use the [+] on the left to search for items');
