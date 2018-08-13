<?=
	html::alert($error, '', ['style' => 'margin-bottom:-50px'])

	->form('', ['class' => 'main_color container', 'target' => '_self', 'style' => 'width:345px; margin:40px auto'])

	->add('<input type="hidden" id="timezone" name="timezone" />')

	->label(form::input('email', data::post('email', ifset($_GET['email'])), 'tall wide'), 'Email', 'margin-top:15px; padding-bottom:0px')

	->label(form::password('password', data::post('password') , 'tall wide'), 'Password', 'padding-bottom:0px')

	->submit("Login", 'avia-size-large');