<?php
class item extends MY_Model
{

/**
| -------------------------------------------------------------------------
|  Select
| -------------------------------------------------------------------------
|
|
*/
	function select()
	{
		return array
		(
			'item' => ['*, id as item_id, name as item_name, description as item_desc'],
			'request' => ['IF(item_id > 0, 1, 0) as requested', 'item_id = item.id']
		);
	}

/**
| -------------------------------------------------------------------------
|  Search
| -------------------------------------------------------------------------
|
| Unlike other searches only return results if there is a search
| Limit to 200 results in order to keep things snappy
|
|
*/

	function search($where = array(), $group_by = '')
	{
		//take the default 'label' value out of the search query
		$where = array_diff($where, ['Name or NDC/UPC']);
		//all search include item type so make sure there is at least one other criteria
		return count(array_filter($where)) > 0 ? self::_search($where, $group_by) : self::obj();
	}

/**
| -------------------------------------------------------------------------
|  Where
| -------------------------------------------------------------------------
|
*/
	function where($field, $value)
	{
		switch($field)
		{
			case 'type':

				$this->db->where('type', $value);

				break;

			case 'upc':
				self::universal($value);
				break;

			default:
				parent::where($field, $value);
				break;
		}
	}

//Allow a universal search box that looks at upc, name, and desc
function universal($value)
{
	if (preg_match('/^[0-9]+$/', str_replace('-', '', $value)))
	{
		$type = data::post('type') ?: 'medicine';

		$value = $type::upc($value);

		if (is_array($value))
		{
			$this->db->where("( upc = '".implode("' OR upc = '", $value)."')");
		}
		else if (text::has($value, '%'))
		{
			$this->db->where("upc LIKE '$value'");
		}
		else if ($value)
		{
			$this->db->where('upc', $value);
		}
	}
	else if ($value != 'Name or NDC/UPC')
	{
		$value = str_replace(' ', '%', $value);

		$this->db->where("CONCAT(item.description, item.name, item.mfg) LIKE '%$value%'");
	}
}

/**
| -------------------------------------------------------------------------
|  Update database items by iterating over a CSV file
| -------------------------------------------------------------------------
|
*/
	function csv($lib, $fn, $delim = ',')
	{
			$startRow = $row = 0;
			set_time_limit(0);
			$this->output->enable_profiler(FALSE);
			$this->db->save_queries = false;

			//NADAC.xls being saved as CSV on mac might change line endings
			ini_set("auto_detect_line_endings", true);

	    //TODO upload will not be set if file is above max file size
			$name  = data::post('upload');

			//print_r($_POST);
			//print_r("$lib $fn $delim $name");

			$fh = fopen($name, 'r');

			//Even if we are resuming we need to get the first row
			$lib::$fn(fgetcsv($fh, '', $delim), ++$row);

			$file = file::path('upload', "seek_".basename($name));

			if ($seek = @file_get_contents($file))
			{
				list($row, $pos) = explode(',', $seek);
				$startRow = $row;
				fseek($fh, $pos);
			}

			while (($row < 10000 + $startRow) AND $cols = fgetcsv($fh, '', $delim))
			{
				$lib::$fn($cols, ++$row);

				file_put_contents($file, $row.','.ftell($fh));
			}

			unlink($name);

			if ($row < 10000 + $startRow) {
			  unlink($file);
				return $row;
			}
	}

/**
| -------------------------------------------------------------------------
|  Update database items by iterating over item table ( & requesting a url)
| -------------------------------------------------------------------------
|
*/
	function url($lib, $fn)
	{
		set_time_limit(0);
		$this->output->enable_profiler(FALSE);

		$seek = file::path('upload', "position_table");
		$this->db->save_queries = false;

		$resource = $this->db->select('id, upc, upc_origin, url')->get('item')->result_id;

		if ($lastPosition = @file_get_contents($seek) ?: 0)
		{
			mysqli_data_seek($resource, $lastPosition);
		}

		//http://ellislab.com/forums/viewthread/216954/P15
		while ($cols = mysqli_fetch_array($resource, MYSQL_ASSOC))
		{
			if ($save = $lib::$fn(array_values($cols)))
			{
				log::info("$fn updated ".print_r($save, true));

				$save['updated'] = gmdate(DB_DATE_FORMAT);

				$this->db->where('id', $cols['id']);

				$this->db->update('item', $save);
			}

			file_put_contents($seek, $lastPosition++);
		}

		unlink($seek);
	}

/**
| -------------------------------------------------------------------------
|  helper function for item::url
| -------------------------------------------------------------------------
|
*/
	function curl($url)
	{
		// using curl because libxml_set_streams_context(stream_context_create(['http' => ['timeout' => 2]]));
		// didn't seem to be working for simplexml_load_file or file_get_contents
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_TIMEOUT, 2);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$file = curl_exec($curl);

		curl_close($curl);

		return $file;
	}


}  // END OF CLASS
