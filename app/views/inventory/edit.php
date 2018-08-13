<?=
	form::open(),

	html::label
	(
		form::input('increment', '', '', ['style' => 'width:40px; padding:2px; margin-right:25px;'])

		.html::submit('Save', 'narrow', ['style' => 'padding-right:7px; padding-left:7px;'])

		, 'Add'

		, 'padding:0; margin-top:-2px; height:27px;'

		, 'margin:1px 5px 0px 0px; width:25px'
	);