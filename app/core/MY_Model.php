<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
| -------------------------------------------------------------------------
|  CRUD - Create, Search, Find, Update, Archive, Restore, Delete
| -------------------------------------------------------------------------
|
| MY_Model extends the built-in CI_Model with CRUD operations, special select format
| aliased where, two level error messages, and automated sorting and limits based on
| input from table library.
|
*/

class MY_Model extends CI_Model
{

/**
| -------------------------------------------------------------------------
|  Extendable Variables & Functions
| -------------------------------------------------------------------------
|
| Replace by declaring a static variable or function of the same name within
| the model.  Extend base functionality by calling parent::name
|
*/
	static $primary  = 'id';

	static $created  = 'created';

	static $updated  = 'updated';

	static $archived = 'archived';

	static $result   = 'result';

	static $record   = 'record';

	static $join 	  = 'left';

	static $where	  = array();

	static $table;

/**
| -------------------------------------------------------------------------
|  Select: define the db schema to be used for the search and find functions
| -------------------------------------------------------------------------
| @note by default, selects all from table given by _table() which defaults to the class
| name.  Function should be overridden in almost every model.  Must return an
| array in the following format array('table [as alias]' => array('select field1,
| field2' [, 'this_key = other.key'] [, join type]). Sets table as 'from' if no join
| keys are specified
| @return 2d array with tables as keys and values as array of fields, join keys, join types
*/
	private function _select()
	{
		return [static::_table() => ['*']];
	}

/**
| -------------------------------------------------------------------------
|  Where: switch to translate where array's keys into sql other than equal
| -------------------------------------------------------------------------
| @param $field, key of the where array to translate into sql code
| @param $value, the string or numeric value of the where array
| @note if field not explicity defined in the MY_Model's where function or
| the model extention of this function, then it is passed to _like_or_where()
| @extending Put any conditions to share accross models in here.  Extend model specific
| conditions by creating a where function with a switch in the model and add
| a default case of self::_where($field, $value).  You can also extend if with sql
| outside of the switch statement if there is something that should always be included.
| @return null
*/
	private function _where($field, $value)
	{
		if (substr($field, -6) == '_after')
		{
			$date = date::utc($value, 'U');
			$field = str_replace('_after', '', $field);
			$this->db->where("(UNIX_TIMESTAMP($field) >= $date OR $field = 0)");
		}
		else if (substr($field, -7) == '_before')
		{
			$date = date::utc($value, 'U');
			$field = str_replace('_before', '', $field);
			$this->db->where("(UNIX_TIMESTAMP($field) <= $date AND $field != 0)");
		}
		else
		{
			static::_like_or_where($field, $value);
		}
	}

/**
| -------------------------------------------------------------------------
|  Find: get a specific record
| -------------------------------------------------------------------------
| @param $where, the id of the record or an associative array of search terms
| @note groups by first key of $where if array else the primary key in 'from' table
| @return record if found, blank record if $where is false, else a blank result object
*/
	private function _find($where)
	{
		if ( ! $where)
		{
			return new record;
		}

		if (is_array($where))
		{
			$group = key($where);
		}

		static::_make_select($group);

		if ( ! is_array($where))
		{
			$where = [$group => $where];
		}

		return static::_record($where);
	}

/**
| -------------------------------------------------------------------------
|  Search: query based on an associative array of where parameters
| -------------------------------------------------------------------------
| @param $where, associative array of terms that will be run through _where()
| @param $group, field to group by default is the primary key of the from table
| @note automatically eliminates soft deletes from results by adding where
| archived = 0 unless field defined by static::$archived is present in $where
*/
	private function _search($where = array(), $group = '')
	{
		static::_make_select($group);

		if( ! preg_grep('/'.static::$archived.'/', $where) && ! preg_grep('/'.static::$archived.'/', array_keys($where)))
		{
			$this->db->where(static::_table().'.'.static::$archived, 0);
		}

		return static::_result($where, $group);
	}

/**
| -------------------------------------------------------------------------
|  Create: insert a record along with created/updated datetimes
| -------------------------------------------------------------------------
| @param $data, associative array of values to add to the database
| @param $table, override the default table returned by _table()
| @return insert_id
*/
	private function _create($data, $table = '')
	{
		$table = static::_table($table);

		$data += array
		(
			static::$updated => gmdate(DB_DATE_FORMAT),
			static::$created => gmdate(DB_DATE_FORMAT)
		);

		$this->db->insert($table, $data);

		return $this->db->insert_id();
	}

/**
| -------------------------------------------------------------------------
|  Update: update a record along with its updated datetime
| -------------------------------------------------------------------------
| @param $data, associative array of values to update in the database
| @param $id, primary key of the record to update
| @param $table, override the default table returned by _table()
| @note filters out blank values
| @note fields escaped, to not escape do not use key and instead
| set field with an equal sign e.g. $date = ['field = field + 1']
| @return null
*/
	private function _update($data, $id, $table = '')
	{
		$table = static::_table($table);

		$this->db->where("$table.".static::$primary, $id);

		if ( ! isset($data[static::$updated]))
		{
			$data[static::$updated] = gmdate(DB_DATE_FORMAT);
		}

		foreach ($data as $field => $value)
		{
			if ($value === '') continue;

			if (is_numeric($field))
			{
				$value = explode('=', $value);

				$this->db->set(reset($value), end($value), false);
			}
			else
			{
				$this->db->set($field, $value);
			}
		}

		$this->db->update($table);
	}

/**
| -------------------------------------------------------------------------
|  Delete: hard delete a record along with its updated datetime
| -------------------------------------------------------------------------
| @param $id, primary key of the record to update
| @param $table, override the default table returned by _table()
| @return null
*/
	private function _delete($id, $table = '')
	{
		$table = static::_table($table);

		$this->db->where("$table.".static::$primary, $id);

		$this->db->delete($table);
	}

/**
| -------------------------------------------------------------------------
|  Archive: soft delete a record along with its updated datetime
| -------------------------------------------------------------------------
| @param $id, primary key of the record to update
| @param $table, override the default table returned by _table()
| @return null
*/
	private function _archive($id, $table = '')
	{
		$table = static::_table($table);

		$this->db->where("$table.".static::$primary, $id);

		$this->db->update($table, [static::$archived => gmdate(DB_DATE_FORMAT)]);
	}

/**
| -------------------------------------------------------------------------
| Restore: undelete an archived record and change updated datetime
| -------------------------------------------------------------------------
| @param $id, primary key of the record to update
| @param $table, override the default table returned by _table()
| @return null
*/
	private function _restore($id, $table = '')
	{
		$table = static::_table($table);

		$this->db->where("$table.".static::$primary, $id);

		$update = array
		(
			static::$updated => gmdate(DB_DATE_FORMAT),
			static::$archived => 0
		);

		$this->db->update($table, $update);
	}

/**
| -------------------------------------------------------------------------
| Options: dynamic list of options for a dropdown select
| -------------------------------------------------------------------------
| @param $value, parsable string that will be display in dropdown e.g. 'This {value}'
| @param $where, an associative array of search terms
| @param $key, parsable string for the key, default is the primary key
| @param $default, default option to prepend to top of list
| @return associative array
*/
	private function _options($value, $where = [], $key = '', $default = 'Please Select...')
	{
		  $options = ['' => $default];

		  $key = $key ?: '{'.static::$primary.'}';

		  $this->load->library('parser');

		  foreach (static::search($where) as $row)
        {
            $options[$this->parser->parse_string($key, $row, true)] = $this->parser->parse_string($value, $row, true);
        }

		  asort($options); //ALPHABETIZE

		  return $options;
	}


/**
| -------------------------------------------------------------------------
| Result: add where, sort, limit, and offset. Wrap array in result helper
| -------------------------------------------------------------------------
| @param $where, an associative array of search terms
| @note performs second query to get total rows regardless of any limit set
| @note called by search, counterpart is row()
| @return result object, which won't throw an error for unset properties
*/
	private function _result($where = [], $group)
	{
		static::_make_where($where);

		$order = result::order() ?: static::_table().'.'.static::$updated;

		$offset = result::offset();

		$per_page = result::per_page();

		$this->db->limit($per_page, $offset);

		if ($group != 'none' AND ! $this->db->ar_orderby)
		{
			$this->db->order_by($order, result::dir() ?: 'desc');
		}

		if ($q = $this->db->get())
		{
			$result = new static::$result($q->result(static::$record));

			$result->next = $q->num_rows == $per_page ? $offset/$per_page + 2: false;

			$result->prev = $offset ? $offset/$per_page : false;

			return $result;
		}

		static::_error();
	}

/**
| -------------------------------------------------------------------------
| Row: add where, sort, and offset. Limit to 1 and wrap in record helper
| -------------------------------------------------------------------------
| @param $where, an associative array of search terms
| @note performs second query to get total rows regardless of any limit set
| @note called by find, counterpart is result()
| @return if not empty record object, else empty result object
*/
	private function _record($where = array())
	{
		static::_make_where($where);

		$this->db->limit(1);

		if ($q = $this->db->get())
		{
			return $q->num_rows() === 1 ?  $q->row(1, static::$record) : static::_obj();
		}

		static::_error();
	}

/**
| -------------------------------------------------------------------------
| Obj (helper): blank result object to avoid errors for unset properties
| -------------------------------------------------------------------------
| @note uses class variable so that we don't need multiple instances of it
| @return blank result helper object
*/
	static $obj;

	function _obj()
	{
		return self::$obj ?: (self::$obj = new static::$result);
	}

/**
| -------------------------------------------------------------------------
|  Make Select (helper): build select/from/join/group_by from model's select()
| -------------------------------------------------------------------------
| @param group, field to group by, if empty will use primary key of 'from' table
| @note appends table name to each select clause in array to avoid name conflics,
| @note sets the 'from' table based on the table in the select array without a join
| @return null, $group changed by reference
*/
	private function _make_select( & $group = '')
	{
		foreach (static::select() as $table => $a)
		{
			$alias = end(explode(' as ', $table));

			//look for field following start of line, comma-space, or open parenthesis and ending before (look ahead)
			// comma, space, close parenthasis, or end of line.  On these fields insert the alias or table name
			$this->db->select(preg_replace("/(^|,\s|\()([a-z_*]+?)(?=,|\s|\)|$)/i", "$1$alias.$2$3", $a[0]), false);

			if (empty($a[1]))
			{
				$this->db->from($table);

				if ( ! $group) $group = self::_table().'.'.static::$primary;

				if ($group != 'none') $this->db->group_by($group);
			}
			else
			{
				$this->db->join($table, "$alias.$a[1]", empty($a[2]) ? static::$join : $a[2]);
			}
		}
	}

/**
| -------------------------------------------------------------------------
|  Make Where (helper): builds where based on model's where function and passed where array
| -------------------------------------------------------------------------
| @param $where, associative array of where fields to turn in the sql's where clause
| @note unlike sql, allows where fields to be aliases since will 'anti-alias' them first
| @note calls where non-statically so that where's switch can have full CI functionality
| @note skips blank where values and converts values that are strings into numbers when possible
| @return null
*/
	function _make_where($where)
	{

		if ( ! $where) return;
//var_dump($where);
		$where = static::_antialias($where);

		foreach($where as $field => $value)
		{
			if ($value === '') continue;

			//turn value into a number if possible
			static::where( (string) $field, (string) (float) $value === $value ? (float) $value : $value);
      }
	}

/**
| -------------------------------------------------------------------------
|  Antialias (helper): remap fields since SQL doesn't support aliased fields in WHERE clause
| -------------------------------------------------------------------------
| @param $where, associative array of where fields to turn in the sql's where statement
| @ loops through all aliases in the select statement to convert where keys and value
| that refer to aliases into the actual field name
| @return remapped where array
*/
	function _antialias($where)
	{
		$select = strstr($this->db->_compile_select(), 'FROM', true);

		foreach(preg_split("/,(?![^()]*+\\))/", $select) as $alias)
		{
			$alias = explode(' as ', trim($alias));

			foreach ($where as $field => $value)
			{
				unset($where[$field]);

				if ('string' == gettype($field))
				{
					$field = preg_replace('/(^|\s|\()'.preg_quote(end($alias)).'/', str_replace(' ', '', '$1'.reset($alias)), $field);
				}

				if ('string' == gettype($value))
				{
					$value = preg_replace('/(^|\s|\()'.preg_quote(end($alias)).'/', str_replace(' ', '', '$1'.reset($alias)), $value);
				}

				$where[$field] = $value;
			}
		}

		return $where;
	}

/**
| -------------------------------------------------------------------------
| Table (helper): determines correct db table
| -------------------------------------------------------------------------
| @param $table, $table to use otherwise uses the dafault noted below
| @note if set uses model's $table variable, else uses first table in select array
| @return table name
*/
	function _table($table = '')
	{
		return $table ?: static::$table ?: reset(array_keys(static::select()));
	}

/**
| -------------------------------------------------------------------------
| Error
| -------------------------------------------------------------------------
|
*/
	function _error()
	{
		$ci = & get_instance();

		$ci->load->library('log');

		$ci->log->write_log('error', "My Model db query failed:\n".$ci->db->last_query());
	}

/**
| -------------------------------------------------------------------------
| Like or Where (helper): use like or where if key is not in where function
| -------------------------------------------------------------------------
| @param $field, key of the where array which is usually the field name
| @param $value, value of the where array
| @note use like except when field is numeric or contains an operator, or in
| model's $where, $primary, $created, $updated, or $archived
| @return database object
*/
	function _like_or_where($field, $value)
	{
		if (is_numeric($field))
		{
			return $this->db->where($value);
		}

		$where = array_merge(static::$where, [static::$primary, static::$created, static::$updated, static::$archived]);

		if($this->db->_has_operator($field) OR in_array(end(preg_split('/[._\s]/', $field)), $where))
		{
			return $this->db->where($field, $value);
		}

		$value = str_replace(' ', '%', $value);

		return $this->db->where("$field LIKE '%$value%'");
	}

/**
| -------------------------------------------------------------------------
| callStatic: private functions force calls here to ensure late static binding
| -------------------------------------------------------------------------
| @note MY_Model functions can be called both from controllers or modelswithout
| the preceding underscore or called from a model method of the same name with
| self::_name. Because we use self rather than parent (makes late static binding
| forget which model its in), the underscore stops and endless loop of the method
| calling itself. Regardless static:: and get_called_class used throughout MY_Model
| refer to the model's class and not the controllers or my_model's
*/
	static function __callStatic($name, $args)
	{
	  return call_user_func_array([new static, str_replace('__', '_', "_$name")], $args);
	}
}
