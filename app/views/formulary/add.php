<?=
    $requests->warning(100),

	form::open('', ['class' => 'togglecontainer']),

		html::div('floatleft')

			->strong('Search to add items to your formulary')->br()

			->add(form::input('upc', '', 'tall wide inline-block', ['tabindex' => 2, 'placeholder' => 'Search by name or ndc']))

			->toggler(form::submit('Search', 'avia-color-grey avia-size-medium'))

			->toggled($requests->form(false)),

		count($requests) == 0 ? '' :

			html::div('floatright main_color')

				->strong('Select items & quantity to add')

				->div('', form::input('quantity', '', 'tall inline-block', ['style' => 'width:80px; margin-right:5px;', 'placeholder' => 'Quantity'])->submit('Submit', 'avia-size-medium')),

		$requests->none( $_POST ? text::get('no_results', "medicine") : ''),

	form::close();