<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class view extends MY_Library //Not MY_Helper since CI has no view_helper
{
	static $css_loc = '</head>';

	static $js_loc  = '</body>';

/**
| ---------------------------------------------------------
| Full: displays head, view, foot, and times output
| ---------------------------------------------------------
| @param $nav, main navigation to show and tab to highlight
| @param $tab, sub navigation tab to highlight
| @param $v, mixed data extracted and available in view
| @param $file, path to view defaults to view/controller/
| @param $search, show search box in the footer
| @return chainable view object
*/
	function full($nav = '', $title, $v = [], $path = '')
	{
		return self::head($nav, $title)->part($path, $v)->foot();
	}

/**
| ---------------------------------------------------------
| Part: only the content in the view without head and foot
| ---------------------------------------------------------
| @param $file, path to view defaults to view/controller/
| @param $v, mixed data extracted and available in view
| @param $string, if true then returns string other view
| @return view string if static else chainable view object
*/
	function part($path, $v = [], $string = false)
	{
		$path = $path ?: $this->router->base();

		if (get_called_class() == get_class())
		{
			$this->load->view($path, $v, $string);

			return $this;
		}

		return $this->load->view($path, $v, $string);
	}

/**
| ---------------------------------------------------------
| Head: displays doctype, nav, tabs, and flash data
| ---------------------------------------------------------
| @param $nav, main navigation to show and tab to highlight
| @param $tab, sub navigation tab to highlight
| @return chainable view object
*/
  function head($nav = '', $title = '')
  {
		$this->load->library('benchmark');

		$this->benchmark->mark('view_start');

		//Are we an admin in admin view otherwise we are admin or user in user view
		$role = (data::get('admin') AND ! data::get('admin_id')) ? 'admin' : 'user';

		//debug(data::get());
		$v = array
		(
			'nav'				=> ucwords($nav),
			'title'			=> ucwords($title),
			'user_name' 	=> data::get('org_name'),
			'org_id' 		=> data::get('user_id')
		);


		$this->load->view("common/header", $v);

		$this->load->view("common/$role", $v);

		$this->load->view("common/title", $v);

		$this->output->append_output(to::sent());

		return new self();
	}

/**
| ---------------------------------------------------------
| Foot: displays footer links and optional search box
| ---------------------------------------------------------
| @return chainable view object
*/
	function foot()
	{
		$this->load->view('common/footer');

		$this->benchmark->mark('view_end');

		return $this;
	}

/**
| ---------------------------------------------------------
| JS: adds js assets in given order before ending body tag
| ---------------------------------------------------------
| @param func_get_args, js asset code or file without extension
| @note if param contains ';' assumes code otherwise a file
| @return chainable view object
*/
	function js()
	{
		foreach (func_get_args() as $src)
		{
			$src = text::has($src, ';') ? ">$src" : "src='".file::path("js", "$file.js")."'>";

			$js[] = "<script type='text/javascript' $src</script>";
		}

		$this->output->final_output = str_replace(self::$js_loc, implode("\n", $js)."\n".self::$js_loc, $this->output->final_output);

		return $this;
	}

/**
| ---------------------------------------------------------
| CSS: adds css assets in given order before ending head tag
| ---------------------------------------------------------
| @param func_get_args, js asset file without extension
| @return chainable view object
*/
	function css()
	{
		foreach (func_get_args() as $file)
		{
			$css[] = "<link rel='stylesheet' href='".file::path("css", "$file.css")."' type='text/css' />";
		}

		$this->output->final_output = str_replace(self::$css_loc, implode("\n", $css)."\n".self::$css_loc, $this->output->final_output);

		return $this;
	}


} //END OF CLASS AND FILE