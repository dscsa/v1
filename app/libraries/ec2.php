<?php
class Ec2
{

	static function __callStatic($action, $args)
	{
		$access = secure::key('aws_access');
		$secret = secure::key('aws_secret');

		$args[0] +=
		[	'Action' 				=> $action
		,	'AWSAccessKeyId' 		=> $access
		,	'SignatureVersion'	=> 2
		,	'SignatureMethod' 	=> 'HmacSHA1'
		,	'Timestamp' 			=> gmdate("Y-m-d\TH:i:s\Z")
		,	'Version' 				=> "2012-06-01"
		];

		return call_user_func('self::_request', $args[0], $secret);
	}

#
# Notes
# AWS REST API HELPER
# Creates Signed REST Query Requests to Amazon's EC2. List of actions and parameters at
# http://docs.amazonwebservices.com/AWSEC2/latest/APIReference/OperationList-query.html
# Based on docs.amazonwebservices.com/AWSEC2/latest/UserGuide/using-query-api.html
# And Dan Myer's Amazon EC2 PHP Class at http://sourceforge.net/projects/php-ec2/
# NOTE: FAILS WHEN CREATESNAPSHOT DESCRIPTION CONTAINS A " " (space)

	function _request($args, $secret)
	{
		$uri = 'ec2.us-west-1.amazonaws.com';

		ksort($args);

		$args['Signature'] = base64_encode(hash_hmac('sha1', "GET\n$uri\n/\n".http_build_query($args), $secret, true));

		$uri = "https://$uri/?".http_build_query($args);

		$ch = curl_init($uri);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($ch);

		curl_close($ch);

		return simplexml_load_string($result);
	}


}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
