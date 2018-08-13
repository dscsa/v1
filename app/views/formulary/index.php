<?=

	isset($message) ? $message : '',

	form::open('', ['class' => 'togglecontainer'])

		->input('upc', '', "tall inline-block", ['style' => 'width:75%; margin-right:5px', 'autofocus' => true, 'tabindex' => 2, 'placeholder' => 'Add items to your formulary by name or ndc', 'id' => 'addrequest'])

		->input('quantity', '', 'tall narrow inline-block', ['placeholder' => 'limit'])->submit('Add', 'avia-color-grey avia-size-medium', ['style' => 'margin-left:5px', 'onclick' => "jQuery('.autocomplete-suggestions').appendTo(this.form) && this.form.submit(); return false"]),

	$pills->none('No formulary items match your criteria'),

	form::close();