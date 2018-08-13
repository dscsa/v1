<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*
|--------------------------------------------------------------------------
| SIRUM Constants
|--------------------------------------------------------------------------
|
|
*/
define('EMAIL', 'donations@sirum.org');
define('SALESFORCE', 'emailtosalesforce@2qy8hlcx76pomamu16f62wl1y1awt2mjc9ba9norwl7s0xe7na.g-gk8zmae.g.le.salesforce.com');
define('ADDRESS', '3000 El Camino Real, BLDG 4 STE 200, Palo Alto, CA 94306');
define('PHONE', '(650) 488-7434');
define('FAX', '(888) 858-8172');

define('INPUT_DATE_FORMAT', 'm/d/y');
define('DB_DATE_FORMAT', 'Y-m-d H:i:s');

define('MAX_LOGIN_ATTEMPTS', 10);
define('MAX_QUESTION_ATTEMPTS', 5);

define('HOST_IP_ADDRESS', gethostbyname(gethostname()));

/* End of file constants.php */
/* Location: ./application/config/constants.php */
