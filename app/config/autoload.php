<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| AUTO-LOADER
| -------------------------------------------------------------------
| This file specifies which systems should be loaded by default.
|
| In order to keep the framework as light-weight as possible only the
| absolute minimal resources are loaded by default. For example,
| the database is not connected to automatically since no assumption
| is made regarding whether you intend to use it.  This file lets
| you globally define which systems you would like loaded with every
| request.
|
*/

//Allow models and libraries to autoloaded when called statically
//if they need to be initialized then load them as normal
function __autoload($class)
{
	
    if (strpos($class, 'CI_') !== 0 AND strpos($class, 'MY_') !== 0 )
    {
		$class = str_replace('\\', '/', $class);
		
		if (preg_match('#(helpers|models|libraries)#', $class))
		{
			include_once APPPATH.$class.EXT;
		}
		else if (file_exists(APPPATH.'helpers/'.$class.EXT))
		{
			include_once APPPATH.'core/'.config_item('subclass_prefix').'Library'.EXT;

			include_once APPPATH.'core/'.config_item('subclass_prefix').'Helper'.EXT;
			
			include_once APPPATH.'helpers/'.$class.EXT;
		}
		else if (file_exists(APPPATH.'models/'.$class.EXT))
		{
			include_once BASEPATH.'core/Model'.EXT;
			
			include_once APPPATH.'core/MY_Model'.EXT;
			
			include_once APPPATH.'models/'.$class.EXT;
		}
		else if (file_exists(APPPATH.'libraries/'.$class.EXT)) 
		{
			include_once APPPATH.'core/'.config_item('subclass_prefix').'Library'.EXT;

			include_once APPPATH.'libraries/'.$class.EXT;
		}
		else if (file_exists(APPPATH.'controllers/'.$class.config_item('controller_suffix').EXT))
		{	
			include_once APPPATH.'controllers/'.$class.config_item('controller_suffix').EXT;
		}
		else
		{
			// Assume this is a class extension request
			include_once BASEPATH.'libraries/'.$class.EXT;

			include_once APPPATH.'libraries/'.config_item('subclass_prefix').$class.EXT;	
		}
    }       
}

/*
| -------------------------------------------------------------------
| Instructions
| -------------------------------------------------------------------
|
| These are the things you can load automatically:
|
| 1. Packages
| 2. Libraries
| 3. Helper files
| 4. Custom config files
| 5. Language files
| 6. Models
|
*/



/*
| -------------------------------------------------------------------
|  Auto-load Packges
| -------------------------------------------------------------------
| Prototype:
|
|  $autoload['packages'] = array(APPPATH.'third_party', '/usr/local/shared');
|
*/

$autoload['packages'] = array(APPPATH.'third_party');


/*
| -------------------------------------------------------------------
|  Auto-load Libraries
| -------------------------------------------------------------------
| These are the classes located in the system/libraries folder
| or in your application/libraries folder.
|
| Prototype:
|
|	$autoload['libraries'] = array('database', 'session', 'xmlrpc');
*/

$autoload['libraries'] = array('database','session');


/*
| -------------------------------------------------------------------
|  Auto-load Helper Files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['helper'] = array('url', 'file');
*/

$autoload['helper'] = array('php');

/*
| -------------------------------------------------------------------
|  Auto-load Config files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['config'] = array('config1', 'config2');
|
| NOTE: This item is intended for use ONLY if you have created custom
| config files.  Otherwise, leave it blank.
|
*/

$autoload['config'] = array();


/*
| -------------------------------------------------------------------
|  Auto-load Language files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['language'] = array('lang1', 'lang2');
|
| NOTE: Do not include the "_lang" part of your file.  For example
| "codeigniter_lang.php" would be referenced as array('codeigniter');
|
*/

$autoload['language'] = array('confirm', 'email', 'permission', 'to');


/*
| -------------------------------------------------------------------
|  Auto-load Models
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['model'] = array('model1', 'model2');
|
*/

$autoload['model'] = array();

/* End of file autoload.php */
/* Location: ./application/config/autoload.php */