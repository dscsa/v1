<?php

//Permission Errors

$lang['permission_user'] = "You do not have permission to access {0}";

$lang['permission_admin'] = "You must be an admin to access {0}";

$lang['login_unknown'] = "We could not recognize that email address";

$lang['login_attempt'] = "Incorrect password. ".html::link('password/reset/{0}', 'Reset your password');

$lang['question_attempt'] = "Incorrect answer. ".html::mail(EMAIL, 'Email us for help');

$lang['no_results'] = "We could not find any {0} matching your criteria. Please try again or ".html::mail(EMAIL, 'email us for help');