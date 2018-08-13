<?=
	html::form()

		->add($donors->fields
		(
			['Donor', 'org_name', 'partner()'],
			['License', 'license', 'donor_license()'],
			['Joined', 'date_donor', 'date(created)'],
			[html::div('main_color')->submit("Save Selection", 'avia-color-theme-color'), '', 'checkbox(approve, org_id)']
		));