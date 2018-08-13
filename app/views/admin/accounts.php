<?=
	html::div('togglecontainer')

		->strong($results->sum('num_donations').' donations from '.count($results).' members', 'floatright')

		->toggler('', 'top:5px')->toggled($results->form()),

	$results->none('No members have registered yet');
