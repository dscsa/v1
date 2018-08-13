<?php
	if (isset($_GET['to']))
	{
		echo html::strong('', 'main_color')->link($_GET['to'], "Back to $_GET[to] page");
	}

	echo "<iframe src='/doc/SIRUM - ".strtoupper($register)." USER AGREEMENT.pdf' width='100%' height='700px'></iframe>";
