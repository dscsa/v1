<?=
	html::form('', ['class' => 'main_color'], ['user_id' => $org->org_id])

		->div('floatright')

			->label($editable ? form::update('account', 'floatright')->upload('upload', '', '', ['style' => 'width:200px']) : form::submit('Edit Profile', 'floatright'), '', 'margin-right:10px')

			->add($org->large_pic())

		->end()

		->div('floatleft')

			->add($editable ? $org->input('org_name', 'tall wide') : html::h3($org->org_name)->div('row', $org->agreement_links()))

			->label($editable ? '' : html::div('row', $org->user_name.' on '.$org->date('last_login', 'F jS \a\t g:ia', 'Never logged in')), $editable ? '' :'Last Login', 'height:auto; margin-bottom:0px;', 'text-align:left;')

			->label($editable ? form::dropdown('license', $org->license, defaults::license(), 'wide') : html::div('row', defaults::license($org->license)), 'License', '', 'text-align:left;')

			->label($editable ? $org->input('phone', 'wide') : html::div('row', $org->phone), 'Phone', '', 'text-align:left;')

			->label($editable ? $org->input('street', 'wide').html::div('inline-block', $org->input('city')).', '.html::div('inline-block', $org->input('state', 'narrow')).' '.html::div('inline-block', $org->input('zipcode', 'narrow')) : html::div('row', $org->street.br(1).$org->city.', '. $org->state.' '.$org->zipcode), 'Address', 'margin-bottom:0px;', 'text-align:left;')

			->label($editable ? $org->text('description', 'wide') : html::div('row', $org->description ?: 'None'), 'Description', '', 'text-align:left;')

		->end()

		->label($editable ? $org->text('instructions', '', ['style' => 'width:100%', 'rows' => 10]) : $org->html_instructions ?: 'None', 'Instructions<br>', 'float:left; width:100%;', 'text-align:left;');
?>