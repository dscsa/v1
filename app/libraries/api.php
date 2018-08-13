<?php
class Api
{

/**
| -------------------------------------------------------------------------
|  Geolocate
| -------------------------------------------------------------------------
|
| This function return the county latitude and longitude of an adress
|
| @param string address, options
| @return array
|
*/
	function geolocate($address)
	{
		$url = 'http://maps.google.com/maps/api/geocode/json?address='.str_replace(' ', '%20', $address).'&sensor=false';

		$ctx = stream_context_create(array( 'http' => array('timeout' => 1 ) ) );

		if ( ! $data = @file_get_contents($url, 0, $ctx))
		{
			return array();
		}

		$json = json_decode($data);

		if(empty($json->status) OR $json->status != 'OK')
		{
			return array();
		}

		foreach ($json->results['0']->address_components as $index => $value)
		{
			if ($json->results['0']->address_components[$index]->types['0'] == 'administrative_area_level_2')
			{
				$county = $json->results['0']->address_components[$index]->short_name;

				break;
			}
		}

		return array
		(
			'county' => ifset($county, 'Unknown'),
			'latitude' => $json->results['0']->geometry->location->lat,
			'longitude' =>$json->results['0']->geometry->location->lng
		);
	}
}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/