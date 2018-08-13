<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class result extends ArrayObject
{
/**
| -------------------------------------------------------------------------
| Call Magic Method
| -------------------------------------------------------------------------
|
| Pass unknown propertoes back to CI instance
|
*/
	static $ci;

	static $per_page;

	static $offset;

	static $order;

	static $dir;

	static $fields = array();

	static $query = array();

	static $none_txt;

	static $base_url;

	static $heading;

	static $form;

	static $show = false;

	public $prev;

	public $next;

/**
| -------------------------------------------------------------------------
| Get: magic method to pass unset properties to the record object or CI
| -------------------------------------------------------------------------
*/
	function __get($name)
	{
		if(isset($this[0],  $this[0]->$name))
		{
			return $this[0]->$name;
		}

		if ( ! self::$ci) self::$ci = & get_instance();

		// If propety is not set, we don't want to throw an error
		return isset(self::$ci->$name) ? self::$ci->$name : null;
	}

/**
| -------------------------------------------------------------------------
| Call: magic method to pass undefined functions to the record object
| -------------------------------------------------------------------------
*/
	function __call($name, $args)
	{
		return isset($this[0]) ? call_user_func_array([$this[0], $name], $args) : '';
	}

/**
| -------------------------------------------------------------------------
| per_page: returns the limit parameter with $per_page as a default
| -------------------------------------------------------------------------
| @return the number of rows by which to limit the results
*/
	function per_page()
	{
		if ( ! self::$per_page)
		{
			self::$per_page = get_instance()->input->get('per_page') ?: 500;
		}

		return self::$per_page;
	}



/**
| -------------------------------------------------------------------------
| Offset: calculates starting row for db based on get array's p parameter
| -------------------------------------------------------------------------
| @return the number of rows by which to offset the results
*/
	function offset()
	{
		if ( ! self::$offset)
		{
			self::$offset = self::per_page() * max(get_instance()->input->get('p') - 1, 0);
		}

		return self::$offset;
	}


	//Basic array_unshift for an ArrayObject.
	function prepend($value) {
			$array = (array) $this;
			array_unshift($array, $value);
			$this->exchangeArray($array);
	}

/**
| -------------------------------------------------------------------------
| Order: calculates order_by of for db based on get array's o parameter
| -------------------------------------------------------------------------
| @return the field by which the results should be ordered by
*/
	function order()
	{
		if ( ! self::$order)
		{
			self::$order = get_instance()->input->get('o');
		}

		return self::$order;
	}

/**
| -------------------------------------------------------------------------
| Dir: calculates order direction for db based on get array's d parameter
| -------------------------------------------------------------------------
| @return the string 'asc' or 'desc'
*/
	function dir()
	{
		if ( ! self::$dir)
		{
			self::$dir = get_instance()->input->get('d');
		}

		return self::$dir;
	}

/**
| -------------------------------------------------------------------------
| Fields: define fields that will appear in the search form and tables
| -------------------------------------------------------------------------
| @param func_get_args, array of strings (label, field, record::func(), form::func())
| both record and form methods should be strings and can take any number of parameters
| @elem label is the text to display in the table heading and/or search field
| @elem name is record property to sort by and display in table if record::func not set
| @elem if set record::func() is displayed in table rather than the property itself
| @elem if set form::func() displays this field in result helper's form. Unlike other
| elems this can be an associative array to display more than one input per field.
| The keys of this array will be used as the search names.
| @note all names are "saved" in flashdata to persist sorting/pagination
| @return if called statically (in the controller) returns an associative array of all
| terms submitted in the search form, else returns a chainable object
*/
	function fields()
	{
		$this->load->library('table');

		$fields = func_get_args();

		self::$base_url = $this->uri->uri_string();

		self::$offset = self::offset();

		self::$order = self::order();

		self::$dir = self::dir();

		if ( ! self::$query) self::$query = data::sent(self::$base_url, array());

		foreach ($fields as $field)
		{
			list($label, $field, $result, $search) = array_pad( (array) $field, 4, false);

			self::$query = array_merge(self::$query, self::_form($label, $field, $search));

			if ($label != 'subrow') self::$heading[] = self::_heading($label, $field);
		}

		self::$fields = array_merge(self::$fields, $fields);

		data::send(self::$base_url, self::$query);

		return get_called_class() == get_class() ? $this : self::$query;
	}

/**
| -------------------------------------------------------------------------
| Form: builds search form based on search::funcs supplied in fields()
| -------------------------------------------------------------------------
| @param tag, if true surronds form with <form></form> set to false if custom
| search fields are made outside of this form.
| @param columns, number of columns for the form's format
| @return a search form defined by the form elements in self::fields
*/
	function form($tag = true, $columns = 3)
	{
		$form = $this->table->make_columns(self::$form, $columns);

		return ($tag ? form::open() : '').$this->table->generate($form).($tag ? form::close() : '');
	}

/**
| -------------------------------------------------------------------------
| None: message to display if no results are found
| -------------------------------------------------------------------------
| @param $txt, text to display rather than a table if count(result) is 0
| @returns chainable object
*/
	function none($txt = '')
	{
		self::$none_txt = html::note($txt);

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Format: custom row and cell styles to apply in addition to row & cell claddes
| -------------------------------------------------------------------------
| @param $row, css style to apply to row and row_alt in addition to those classes
| @param $cell, css style to apply to each cell in addition the cell class
| @param $none, css style to apply to the none msg in addition the none class
| @returns chainable object
*/
	function format($row = '', $cell = '', $none = '')
	{
		$custom = array
		(
			'table_open' 	 => "<table>",
			'row_start'		 => "<tr  class='main_color' style='$row' >",
			'row_alt_start' => "<tr  class='main_color' style='$row' >",
			'cell_start'	 => "<td  style='$cell' >",
			'cell_alt_start'=> "<td  style='$cell' >",
			'none_start'	 => "<div style='$none'>"
		);

		$this->table->set_template($custom + $this->table->_default_template());

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Trigger: displays the table defined by fields, format, & none functions
| -------------------------------------------------------------------------
| @note rather than ending a method chain with a trigger, when results is displayed
| in the view it will show a table using CI's table and pagination classes.
| Configuration of this table can be set using the fields, format, & none
| functions. Table is given the class result.
*/
	function __toString()
	{
		self::format();

		$table = $this->table->template['none_start'].self::$none_txt."</div>";

		if (count($this))
		{
			$this->table->set_heading(self::$heading);

			$next = $prev = $this->input->get();

			$next['p'] = $this->next;

			$prev['p'] = $this->prev;

			$page = '<div class="page">';

			if ($this->prev)
			{
				$page .= html::link(self::$base_url."?".http_build_query($prev), '&lsaquo; prev');

				if ($this->next)
				{
					$page .= ' | ';
				}
			}

			if ($this->next)
			{
				$page .= html::link(self::$base_url."?".http_build_query($next), 'next &rsaquo;');
			}

			$page .= '</div>';

			$table = $this->table->generate(self::_rows()).$page;
		}

		return $table;
	}

/**
| -------------------------------------------------------------------------
| Form: creates the 2d array from which form will be displayed
| -------------------------------------------------------------------------
| @param $label = strtolower($field && $key ? $field.'_'.$key : ($field ?: $key))
| @param $field, name to be given the the form element displayed
| @param $methods, the actual type of input to be used, ie input, textarea, submit
| @note private method
*/
	function _form($label, $field, $methods)
	{
		$this->load->helper('form');

		foreach( (array) $methods as $key => $method)
		{
			$name = strtolower(($field and $key) ? $field.'_'.$key : ($field ?: $key));

			$value[$name] = self::value($name);

			if(preg_match('/(.*)\((.*)\)/', str_replace(', ', ',', $method), $match))
			{
				self::$show = self::$show ?: ((bool) $value[$name] and $match[1] != 'radio');

				//form::submit's arguments are value, class, attributes
				$args = explode(',', $match[2], 3);

				//other form functions also need a name, value
				if ($match[1] != 'submit') array_unshift($args, $name, $value[$name]);

				$elem = call_user_func_array(['form', $match[1]], $args);

				self::$form[] = "<label><span>".($name ? ucwords(str_replace([$field, '_'], [$label, ' '], $name)) : '')."</span>$elem</label>";
			}
		}

		return $value;
	}

/**
| -------------------------------------------------------------------------
| Value: creates the search form defined by fields()
| -------------------------------------------------------------------------
| @param $name, key of the specific post element to retrive from the form
| @param $default, the string to return if the value of name is not set
| @note unlike the post array value is kept on sort & page changes
*/
	function value($name, $default = '')
	{
		  //Use post data if there, otherwise use flashdata if the results are ordered or paginated
		  $result = get_instance()->input->post($name) ?: ((isset(self::$query[$name]) AND (self::$offset OR self::$order)) ? self::$query[$name] : $default);

		  return is_string($result) ? trim($result) : $result;
	}

/**
| -------------------------------------------------------------------------
| Heading: shows fields label in table heading along with sort graphic
| -------------------------------------------------------------------------
| @note private method
| @return html hyperlink for the table headings
*/
	//Save field but don't actually display labels for sub_rows if ($h[0] == 'subrow') continue;
	function _heading($label, $field)
	{
		if ( ! $field) //Only make column sortable if field is given
		{
			return $label;
		}

		if($field !== self::$order) //If not sorted by this field then show non-sorted graphic
		{
			return anchor(self::$base_url.$this->router->get_string(['o' => $field, 'd' => 'desc']), $label.html::image('table/bg.gif','border=0'), ['class' => 'main_color']);
		}

		if (self::$dir == 'asc') //If ascending, sort by descending and show descending graphic
		{
			return anchor(self::$base_url.$this->router->get_string(['d' => 'desc']), $label.html::image('table/desc.gif','border=0'), ['class' => 'main_color']);
		}

		//Otherwise, sort by ascending and show ascending graphic
		return anchor(self::$base_url.$this->router->get_string(['d' => 'asc']), $label.html::image('table/asc.gif','border=0'), ['class' => 'main_color']);
	}

/**
| -------------------------------------------------------------------------
| Rows: returns rows of the current page
| -------------------------------------------------------------------------
| @note private method
| @return two diminsional array of rows for the table
*/

	function _rows()
	{
		foreach($this as $i => $data)
		{
			$row = [];

			foreach (self::$fields as $j => $field)
			{
				list($label, $field, $result, $search) = array_pad( (array) $field, 4, false);

				$value = isset($data->$field) ? $data->$field : '&nbsp;';

				if (preg_match('/(.*)\((.*)\)/', str_replace(' ', '', $result), $match))
				{
					$value = (string) call_user_func_array([$data, $match[1]], explode(',', $match[2]));
				}

				//if more fields than headings then append a sub row to previous field
				if ($j == count($this->table->heading))
				{
					//debug($row[0]);
					$row[0] = html::toggler('', 'top:-15px', $i+2)->add($row[0])->toggled($value);
				}
				// If not a sub row then lets just add a new column
				else
				{
					$row[$j] = $value;
				}

			}

			$return[] = $row;
		}

		return $return;
	}


/**
| -------------------------------------------------------------------------
|  Sum: Sum the specified key over given multi-dimensional object array;
| -------------------------------------------------------------------------
| @param $key, adds up result[0]->$key + result[1]->$key + ....
| @return numeric sum
*/
	function sum($key, $digits = 2)
	{
		$sum = 0;

		foreach ($this as $record)
		{
			$sum += $record->$key;
		}

		return round($sum, $digits);
	}

/**
| -------------------------------------------------------------------------
|  Sort: order by key over the given multi-dimensional object array;
| -------------------------------------------------------------------------
| @param $key to sort in ascending? order
| @return chainable object
*/
	function sort($key)
	{
		$this->uasort(function($a, $b) {  if ($a->$key == $b->$key) { return 0; } return ($a->$key < $b->$key) ? -1 : 1;});

		return $this;
	}

/**
| -------------------------------------------------------------------------
|  Warning: displays error div if query returns more results than limit
| -------------------------------------------------------------------------
| @param $limit, threshold number of results at which to return an error
| @return html string
*/
	function warning($limit)
	{
		return count($this) > $limit ? html::div('error', 'Displaying top results of over '.count($this).' matches. Please refine your search') : '';
	}


} //End of class





/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
