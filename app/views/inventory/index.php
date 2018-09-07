<?=

	isset($message) ? $message : '',

	form::open('', ['class' => 'togglecontainer', 'style' => 'white-space:nowrap'])
		->upload('upload', 'Import', 'floatright avia-color-theme-color-subtle', ['hidden' => true, 'onchange' => 'this.form.submit()'])

		->submit("Export", 'floatright avia-color-theme-color-subtle '.( $inventory->sum('count') ? '': 'avia-color-silver'), ['rel' => 'lightbox', 'style' => 'border-color:#e1e1e1; margin-left:2px', $inventory->sum('count') ?: 'disabled' => true, 'onclick' => "return confirm('Are you sure you want to delete all these items and export them as an FTP file to update your QS/1 inventory?  This cannot be undone.')"])
		 ->submit("Get Errors", 'floatright avia-color-theme-color-subtle')


		->input('upc', '', "tall inline-block", ['style' => 'width:65%', 'autofocus' => true, 'tabindex' => 2, 'placeholder' => 'Add or filter items by name or ndc', 'id' => 'addinventory']),

	html::toggler(form::submit('Search'))

		 ->toggled($inventory->form(false)),

	$inventory->none('No inventory items match your criteria'),

	form::close();
