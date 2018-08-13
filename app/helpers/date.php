<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class date
{
	static $timezone;

	private function timezone($org_id)
	{
		if ( ! $org_id && self::$timezone)
		{
			return self::$timezone;
		}

		//[Standard Time, Daylight Time]
		$timezones =
		[
			'AL' => [-6, -5],
			'AK' => [-9, -8],
			'AK' => [-10, -10],
			'AZ' => [-7, -7],
			'AR' => [-6, -5],
			'CA' => [-8, -7],
			'CO' => [-7, -6],
			'CT' => [-5, -4],
			'DC' => [-5, -4],
			'DE' => [-5, -4],
			'FL' => [-5, -4],
			'GA' => [-5, -4],
			'HI' => [-10, -10],
			'ID' => [-8, -7],
			'IL' => [-6, -5],
			'IN' => [-5, -4],
			'IA' => [-6, -5],
			'KS' => [-6, -5],
			'KY' => [-5, -4],
			'LA' => [-6, -5],
			'ME' => [-5, -4],
			'MD' => [-5, -4],
			'MA' => [-5, -4],
			'MI' => [-5, -4],
			'MN' => [-6, -5],
			'MS' => [-6, -5],
			'MO' => [-6, -5],
			'MT' => [-7, -6],
			'NE' => [-6, -5],
			'NV' => [-8, -7],
			'NH' => [-5, -4],
			'NJ' => [-5, -4],
			'NM' => [-7, -6],
			'NY' => [-5, -4],
			'NC' => [-5, -4],
			'ND' => [-6, -5],
			'OH' => [-5, -4],
			'OK' => [-6, -5],
			'OR' => [-8, -7],
			'PA' => [-5, -4],
			'RI' => [-5, -4],
			'SC' => [-5, -4],
			'SD' => [-6, -5],
			'TN' => [-5, -4],
			'TX' => [-6, -5],
			'UT' => [-7, -6],
			'VT' => [-5, -4],
			'VA' => [-5, -4],
			'WA' => [-8, -7],
			'WV' => [-5, -4],
			'WI' => [-6, -5],
			'WY' => [-7, -6]
		];

		//Be careful! If session state not set and we do a database query with org::find
		//this could interupt a partially built query from My_Model::_where.  This was happening
		//when searching by status date on the donations page
		$state = data::get('state') ?: org::find($org_id ?: data::get('org_id'))->state;

		return self::$timezone = $timezones[$state ?: 'CA'][date('I')];
	}

/**
| ---------------------------------------------------------
| Format: clean and format user supplied date
| ---------------------------------------------------------
| @param $date, date string supplied by user
| @param $format, format that overrides user_date_format
| @param $offset, number of hours by which to adjust date
| @return formatted date string
*/
	function format($date = '', $format = '', $offset = 0)
	{
		if ($date == '0000-00-00 00:00:00' OR $date == '0000-00-00')
		{
			return null;
		}

		if (substr_count($date, '-') != 3)
		{	//three dashes means that date has a timezone and doesn't need to be sanitized e.g. 2013-01-01T13:00-07:00
			$date = strtolower(str_replace(['.', '-', ','], "/", strip_tags($date)));
		}

		if (substr_count($date, '/') == 1)
		{	//one dash means its in a mm/yy or yy/mm format (expiration dates).
			//If day is not inserted, date() will incorrectly believe it is mm/dd
			list($m, $y) = explode('/', $date);

			$date = $m <= 12 ? "$m/01/$y" : "$y/01/$m";
		}

		if (date_create($date) AND date('U', strtotime($date)))
		{
			$date = date($format ?: INPUT_DATE_FORMAT, strtotime($date) + $offset * 60 * 60);
		}

		return $date;
	}

/**
| ---------------------------------------------------------
| Local: date minus session timezone, defaults to PST
| ---------------------------------------------------------
| @param $date, date string supplied by user
| @param $default, default that overrides default_db_date
| @param $format, format that overrides user_date_format
| @param $nowrap, if true wraps date in a nowrap div
| @param $hours, number of hours by which to adjust date
| @return formatted date string
*/
	function local($date = '', $format = '', $org_id = '', $offset = 0)
	{
		return self::format($date, $format, $offset + self::timezone($org_id));
	}

/**
| ---------------------------------------------------------
| Utc: date plus session timezone, defaults to PST
| ---------------------------------------------------------
| @param $date, date string supplied by user
| @param $default, default that overrides default_db_date
| @param $format, format that overrides to 'c'
| @param $nowrap, if true wraps date in a nowrap div
| @param $hours, number of hours by which to adjust date
| @return formatted date string
*/
	function utc($date = '', $format = '', $org_id = '', $offset = 0)
	{
		return self::format($date, $format, $offset - self::timezone($org_id));
	}

} //END OF CLASS AND FILE
