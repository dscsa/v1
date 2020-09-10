<?php
class fedex extends MY_Library
{
	static $path = '../app/libraries/fedex/';

	function _request($func, $request)
	{
		$soap     = self::_soap($func, $request);
		$response = $soap->$func($request);

		if (is_soap_fault($response))
		{
			//Throw error if faulty internet connection
			return self::_return('error', log::error("Fedex Library had a problem: $response->faultstring"));
		}

		if ($func == 'getPickupAvailability') {
			log::error("getPickupAvailability Request:\n" .  $soap->__getLastRequest() . "\n");
			log::error("getPickupAvailability Response:\n" . $soap->__getLastResponse() . "\n");
		}

		if ( ! is_object($response) OR in_array($response->HighestSeverity, ['FAILURE', 'ERROR']))
		{
			$message = '';

			if (is_array($response->Notifications))
			{
				foreach ($response->Notifications as $index => $notification)
				{
					$message .= ($index+1)."-  ".$notification->Message."<br>";
				}
			}
			else $message .= $response->Notifications->Message;

			return self::_return('error', strtolower($message));
		}

		return self::_return('success', $response);
	}

	function _return($type, $msg)
	{
		return array_merge(['error' => false, 'success' => false], [$type => $msg]);
	}

	function _soap($func, & $request)
	{
		$request['RequestTimestamp'] = gmdate('c');

		$request['WebAuthenticationDetail'] = array
		(
			'UserCredential' => array
			(
				'Key'      => secure::key('fedex_key'),
				'Password' => secure::key('fedex_password')
			)
		);
		$request['ClientDetail'] = array
		(
			'AccountNumber' => '111729905',
			'MeterNumber'   => '101365579'
		);

		switch($func)
		{
			case 'processShipment':

				$request['TransactionDetail'] = array
				(
					'CustomerTransactionId' => '*** Ground Domestic Shipping Request v7 using PHP ***'
				);

				$request['Version'] = array
				(
					'ServiceId'       => 'ship',
					'Major'           => '7',
					'Intermediate'    => '0',
					'Minor'           => '0'
				);

				$wsdl = self::$path.'ShipService_v7.wsdl';

				break;

			case 'addressValidation':

				$request['TransactionDetail'] = array
				(
					'CustomerTransactionId' => '*** Address Validation Request v2 using PHP ***'
				);

				$request['Version'] = array
				(
					'ServiceId'    => 'aval',
					'Major'        => '2',
					'Intermediate' => '0',
					'Minor'        => '0'
				);

				$wsdl = self::$path.'AddressValidationService_v2.wsdl';

				break;

			case 'track':

				$request['TransactionDetail'] = array
				(
					'CustomerTransactionId' => '*** Track Request v4 using PHP ***'
				);

				$request['Version'] = array
				(
					'ServiceId'       => 'trck',
					'Major'              => '4',
					'Intermediate' => '0',
					'Minor'              => '0'
				);

				$wsdl = self::$path.'TrackService_v4.wsdl';

				break;

			case 'getPickupAvailability':

				$request['TransactionDetail'] = array
				(
					'CustomerTransactionId' => '*** Pickup Availability Request v3 using PHP ***'
				);

				$request['Version'] = array
				(
					'ServiceId'     => 'disp',
					'Major'         => '3',
					'Intermediate'  => '0',
					'Minor'         => '1'
				);

				$wsdl = self::$path.'CourierDispatchService_v3.wsdl';

				break;

			case 'cancelCourierDispatch':

				$request['TransactionDetail'] = array
				(
					'CustomerTransactionId' => ' *** Cancel Courier Dispatch Request v3 using PHP ***'
				);

				$request['Version'] = array
				(
					'ServiceId'     => 'disp',
					'Major'         => '3',
					'Intermediate'  => '0',
					'Minor'        	=> '1'
				);

				$wsdl = self::$path.'CourierDispatchService_v3.wsdl';

				break;

			case 'createCourierDispatch':

				$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Courier Dispatch Request v3 using PHP ***');

				$request['Version'] = array
				(
					'ServiceId'    => 'disp',
					'Major'        => '3',
					'Intermediate' => '0',
					'Minor'        => '1'
				);

				$wsdl = self::$path.'CourierDispatchService_v3.wsdl';

				break;

			default:

				die('Fedex WSDL not found');

		}

    //log::error("new SoapClient($wsdl)");
		return new SoapClient($wsdl, ['exceptions' => 0, 'trace' => 1]);
	}

/**
| -------------------------------------------------------------------------
| address
| -------------------------------------------------------------------------
|
| Custom validation callback for user registration. Checks
| FedEx API to make sure address is in database. Required
| otherwise shipping label generation through FedEx could fail
|
| @param string street
| @param string city, default value is null
| @param string stateProvinceCode, default value is null
| @param string postalCode, default value is null
| @return bool TRUE if username available, false if already taken
*/

    function address($street, $city = null, $stateProvinceCode = null, $postalCode = null)
    {
		$addressArray['StreetLines'] = $street;

		if($city) $addressArray['City'] = $city;

        if($stateProvinceCode) $addressArray['StateOrProvinceCode'] = $stateProvinceCode;

        if($postalCode) $addressArray['PostalCode'] = $postalCode;

		$request['Options'] = array
		(
			'CheckResidentialStatus'      => 1,
			'MaximumNumberOfMatches'      => 5,
			'StreetAccuracy'              => 'LOOSE',
			'DirectionalAccuracy'         => 'LOOSE',
			'CompanyNameAccuracy'         => 'LOOSE',
			'ConvertToUpperCase'          => 1,
			'RecognizeAlternateCityNames' => 1,
			'ReturnParsedElements'        => 1
		);

		$request['AddressesToValidate'] = array
		(
			array
			(
				'AddressId' => 'User Address Validation',
		   	'Address'   => $addressArray
			)
		);

		$response = self::_request('addressValidation', $request);

		if ($response['error'])
		{
			return $response; //for validation purposes this array will evalutate to true
		}

		$address = $response['success']->AddressResults->ProposedAddressDetails;

		if ($address->Score > 30 OR $address->DeliveryPointValidation == "UNAVAILABLE")
		{
			return true;
		}

		log::error($response['success']);

		return false;
	}

/**
| -------------------------------------------------------------------------
|  Label
| -------------------------------------------------------------------------
|
| Uses FedEx API to create a shipping label based on the donor input on the donation
| details page.  If donor used one of th pre-populated sizes, function passes that information
| on to the API.
|
| @param int donation_id, transaction id for the donation is required
| @param array packageinfo, options
| @param string item_names, options
|
*/


	function label($file, $donation, $singlestream = FALSE)
	{
		$request['RequestedShipment'] = array
		(
			/*'SmartPostDetail' =>
			[
				'Indicia' => 'PRESORTED_STANDARD',
				'HubID' => 5902
			],*/
			'ShipTimestamp'    => gmdate('c'),//date('Y-m-d\TH:i:s')."-07:00"replace gmdate(c) of PHP 5.  07:00 is GMT offset
			'DropoffType'      => 'REGULAR_PICKUP', // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and STATION
			'ServiceType'      => 'FEDEX_GROUND', // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, SMART_POST...
			'PackagingType'    => 'YOUR_PACKAGING', // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...

			'Shipper' => array
			(

				'Contact' => array
				(
					'CompanyName' => $donation->donor_org,
					'PhoneNumber' => PHONE,
				),
				'Address'     => array
				(
					'StreetLines' => array
					(
						$donation->donor_street
					),
					'City'                => $donation->donor_city,
					'StateOrProvinceCode' => $donation->donor_state,
					'PostalCode'          => $donation->donor_zip,
					'CountryCode'         => 'US'
				)
			),
			'Recipient' => array
			(
				'Contact' => array
				(
					'CompanyName' => $donation->donee_org,
					'PhoneNumber' => PHONE,
				),
				'Address' => array
				(
					'StreetLines' => array
					(
						$donation->donee_street
					),
					'City'                => $donation->donee_city,
					'StateOrProvinceCode' => $donation->donee_state,
					'PostalCode'          => $donation->donee_zip,
					'CountryCode'         => 'US'
				)
			),
			'ShippingChargesPayment' => array
			(
				'PaymentType' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
				'Payor'       => array
				(
					'AccountNumber'  => '111729905', // Replace 'XXX' with your account number
					'CountryCode'    => 'US'
				)
			),
			'LabelSpecification' => array
			(
				'LabelFormatType' => 'COMMON2D', // valid values COMMON2D, LABEL_DATA_ONLY
				'ImageType'       => 'PNG',  // valid values DPL, EPL2, PDF, ZPLII and PNG
				'LabelStockType'  => 'PAPER_4X6'
			),
			'RateRequestTypes' => array
			(
				'ACCOUNT'
			), // valid values ACCOUNT and LIST
			'PackageCount'              => 1,
			'PackageDetail'             => 'INDIVIDUAL_PACKAGES',
			'RequestedPackageLineItems' => array
			(
				'0' => array
				(
					'SequenceNumber' => '1',
					'Weight'         => array
					(
						'Value' => '2',
						'Units' => 'LB'
					), // valid values LB or KG
					'Dimensions' => array
					(
						'Length' => '14',
						'Width'  => '14',
						'Height' => '10',
						'Units'  => 'IN'
					), // valid values IN or CM
					'CustomerReferences' => array
					(
						'0' => array
						(
							'CustomerReferenceType' => 'CUSTOMER_REFERENCE',
							'Value' => text::between($file)
						), // valid values CUSTOMER_REFERENCE, INVOICE_NUMBER, P_O_NUMBER and SHIPMENT_INTEGRITY
						'1' => array
						(
							'CustomerReferenceType' => 'INVOICE_NUMBER',
							'Value' => substr($donation->donor_org, 0, 28)
						),
						'2' => array
						(
							'CustomerReferenceType' => 'P_O_NUMBER',
							//Might not be set for donee boxes
							'Value' => substr(ifset($donation->donor_user), 0, 28)
						),
					),
				)
			)
		);

		$response = self::_request('processShipment', $request);

		if ($response['error'])
		{
			return $response;
		}

		imagepng(imagecreatefromstring($response['success']->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image), "$file.png");

		return self::_return('success', ['tracking_number' => $response['success']->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber]);

	}

/**
| -------------------------------------------------------------------------
|  Track
| -------------------------------------------------------------------------
|
| This function is called from donation and home controller.
|
| @param string tracking_number
| @return arrary
| 2192
*/
	function track($tracking_number, $unique_id = '')
  {

		$request['PackageIdentifier'] = array
		(
			'Value'  => $tracking_number,
			'Type'   => 'TRACKING_NUMBER_OR_DOORTAG'
		);

		if ($unique_id)
		{
			$request['TrackingNumberUniqueIdentifier'] = $unique_id;
		}

		$response = self::_request('track', $request);

		if ($response['error'])
		{
			if (isset($response['error']->Notifications->Code) && $response['error']->Notifications->Code != 9040) //Tracking information not received by FedEx bullshit
			{
				log::error($response['error']);
			}

			//sometime fedex just messes up but we need this for it to reschedule.
 			if ($response['error'] == 'this tracking number cannot be found. please check the number or contact the sender.')
				return self::_return('success', ['order_created' => null]);

			return $response; //for validation purposes this array will evalutate to true
		}

		//Tracking numbers are not unique.  If more than one package matches a tracking number pick the last one
		if (is_array($response['success']->TrackDetails))
		{
			return self::track($tracking_number, end($response['success']->TrackDetails)->TrackingNumberUniqueIdentifier);
		}

/* Code Definitions
https://www.fedex.com/us/developer/WebHelp/ws/2015/html/WebServicesHelp/WSDVG/4_Tracking_and_Visibility_Services.htm

PF - Plane in Flight,
AA-At Airport
PL-Plane Landed
AC-At Canada Post facility
PM-In Progress
AD-At Delivery
PU-Picked Up
AF-At FedEx Facility
PX-Picked up (see Details)
AP-At Pickup
RR-CDO requested
AR-Arrived at
RM-CDO Modified
AX-At USPS facility
RC-CDO Cancelled
CA-Shipment Cancelled
RS-Return to Shipper
CH-Location Changed
RP-Return label link emailed to return sender
DD-Delivery Delay
LP-Return label link cancelled by shipment originator
DE-Delivery Exception
RG-Return label link expiring soon
DL-Delivered
RD-Return label link expired
DP-Departed
SE-Shipment Exception
DR-Vehicle furnished but not used
SF-At Sort Facility
DS-Vehicle Dispatched
SP-Split Status
DY-Delay
TR-Transfer
EA-Enroute to Airport
ED-Enroute to Delivery
CC-Cleared Customs
EO-Enroute to Origin Airport
CD-Clearance Delay
EP-Enroute to Pickup
CP-Clearance in Progress
FD-At FedEx Destination
EA-Export Approved
HL-Hold at Location
SP-Split Status
IT-In Transit
IX-In transit (see Details)
CA-Carrier
LO-Left Origin
RC-Recipient
OC-Order Created
SH-Shipper
OD-Out for Delivery
CU-Customs
OF-At FedEx origin facility
BR-Broker
OX-Shipment information sent to USPS
TP-Transfer Partner
PD-Pickup Delay
SP-Split status
 */

 		log::error('Full FedEx Response for All Donations '.print_r($response, true));

		switch($response['success']->TrackDetails->Events->EventType)
		{
			case 'DL':
				return self::_return('success', ['date_received' => date::format($response['success']->TrackDetails->ActualDeliveryTimestamp, DB_DATE_FORMAT)]);

			case 'PU':
			case 'DP':
			case 'AF':
			case 'PX':
			case 'DD':
			case 'IT':
			case 'IX':
			case 'OD':
			case 'IP':
			case 'AR':
				//shiptimestamp is sometimes the order created (OC) date so use explicit pickup time instead. Because we query different timespans throughout day, catch multiple transit codes as 'shipped'.
				//The donation model code won't repeat any work once DB has a non-null date_shipped (and won't overwrite the original shipped timestamp). this is just a wider net
				log::error(('Full FedEx Response for Shipped Donation '. print_r($response, true));
				return self::_return('success', ['date_shipped' => date::format($response['success']->TrackDetails->Events->Timestamp, DB_DATE_FORMAT), 'address' => $response['success']->TrackDetails->Events->Address]);
			case 'OC':
				return self::_return('success', ['order_created' => null]);

			default:
				//Error checking purposes
				//admin::email('FedEx Tracking Code', print_r($response['success'], true));
				//Not actually an error but we don't want to update the donation
				return self::_return('error', $response['success']->TrackDetails->Events->EventType);
		}
	}

/**
| -------------------------------------------------------------------------
|  Window
| -------------------------------------------------------------------------
|
| This function uses members address to determine the earliest,
| latest, and access times that FedEx requires for their location
|
| @param array donor_info
| @return arrary
|
*/

	function window($donation)
	{
		$request = array
		(
			'PickupAddress' =>
			[	'StreetLines' 				=> [$donation->donor_street]
			,	'City' 						=> $donation->donor_city
			,	'StateOrProvinceCode' 	=> $donation->donor_state
			,	'PostalCode' 				=> $donation->donor_zip
			,	'CountryCode' 				=> 'US'
			],

			'PickupRequestType' => array('FUTURE_DAY'),

			'Carriers' => array('FDXG')
		);

		$response = self::_request('getPickupAvailability', $request);
		log::error("Logging All Pickups: ".print_r($response, true));

		if ($response['error'])
		{
			return $response; //for validation purposes this array will evalutate to true
		}

		return self::_window($response['success']);
	}


	function org_window($org)
	{
		$request = array
		(
			'PickupAddress' =>
			[	'StreetLines' 				=> [$org->street]
			,	'City' 					=> $org->city
			,	'StateOrProvinceCode' 			=> $org->state
			,	'PostalCode' 				=> $org->zipcode
			,	'CountryCode' 				=> 'US'
			],

			'PickupRequestType' => array('FUTURE_DAY'),

			'Carriers' => array('FDXG')
		);

		$response = self::_request('getPickupAvailability', $request);

		if ($response['error'])
		{
			return $response; //for validation purposes this array will evaluate to true
		}

		return self::_window($response['success']);
	}


	function _window($response)
	{
		$close = strtotime('21:00:00');

		$start = strtotime('06:30:00');

		$window = $close - $start;

		$arr = is_array($response->Options) ? $response->Options : [$response->Options];

		foreach ($arr as $option)
		{
			if(is_object($option) AND $option->Available)
			{
				$dates[$option->PickupDate] = gmdate('l, F jS', strtotime($option->PickupDate));

				$cutoff = min($close, strtotime($option->CutOffTime));

				preg_match_all('/[0-9]/', $option->AccessTime, $matches);

				$window = min($window, $matches[0][0]*60*60 + $matches[0][1]*60);
			}
		}

		$options = array('' => 'Please select a pickup window...');

		$temp = $start;

		while ($temp <= $cutoff)
		{
			$options[$temp] = gmdate('g:ia', $temp).' - '.gmdate('g:ia', $temp + $window);

			$temp = strtotime('+30 minutes', $temp);
		}

		return self::_return('success', ['start' => $start, 'window' => $window, 'dates' => $dates, 'options' => $options]);
	}


/**
| -------------------------------------------------------------------------
|  Cancel Pickup
| -------------------------------------------------------------------------
|
| This function take transaction id, courrier number and courrier date
| to ensure courier dispatch cancelation
|
| @param int donation_id
| @param int courier_number
| @param date courier_date
| @return arrary
|
*/

	function cancel_pickup($donation)
	{
		$request =
		[	'CarrierCode' 						=> 'FDXG'  // valid values FDXE-Express, FDXG-Ground, etc
		,	'DispatchConfirmationNumber'	=> $donation->pickup_number
		,	'ScheduledDate' 					=> date::local($donation->date_pickup, 'Y-m-d')
		,	'CourierRemarks' 					=> 'Please call '.PHONE.' if you experience any problems'
		];

		return self::_request('cancelCourierDispatch', $request);
	}

/**
| -------------------------------------------------------------------------
|  Pickup
| -------------------------------------------------------------------------
|
| This function is used to create new courrier dispatch for a specified
| transaction id.  We make a new one using FedEx API
|
|
| @param int donation_id
| @param string donor_info
| @param date date_string
| @param date ready_time
| @param date closing_time
| @return array
|
*/

	function pickup($donation, $start = '', $date = '', $location = '', $contact_name = '')
	{
		for($i = 0; $i < 5; $i++)
		{
			$window = self::window($donation);

			if ($window['success']) break;
		}

		$date = $date ?: key($window['success']['dates']);

		$start = $start ?: $window['success']['start'];

		// this works but lots of calls to extend pickup window -> gmdate('H:i:s', $start + $window['success']['window']);
		$close = '16:00:00'; //to simplify things rather than doing window code above lets just setup at 4pm

		$start = gmdate('H:i:s', $start);

		//Convert local pickup time to UTC
		$date_pickup = date::utc("$date $close", DB_DATE_FORMAT, $donation->donor_id);

		if(ENVIRONMENT != "production")
		{
			return self::_return('success', ['pickup_number' => ENVIRONMENT, 'date_pickup' => $date_pickup]);
		}

		$request = array
		(
			'PackageCount' 			=> '1',
			'TotalWeight' 				=> ['Value' => '10.0', 'Units' => 'LB'],
			'CarrierCode' 				=> 'FDXG',
			'CourierRemarks' 			=> "SIRUM Box @ $location",
			'OversizePackageCount'	=> '0',
			'OriginDetail' 			=> array
			(
				'PickupLocation' => array
				(
					'Contact' => array
					(
						'PersonName'   => $contact_name ?: $donation->donor_user,
						'CompanyName'	=> "$donation->donor_org by SIRUM",
						'PhoneNumber'  => '6504887434' //MUST BE NUMBER e.g. NOT THE FOLLOWING "SIRUM:".PHONE." PICKUP:$donation->donor_phone"
					),
					'Address' => array
					(
						'StreetLines' => array
						(
							$donation->donor_street
						),
						'City'                => $donation->donor_city,
						'StateOrProvinceCode' => $donation->donor_state,
						'PostalCode'          => $donation->donor_zip,
						'CountryCode'         => 'US'
					)
				),
			   'PackageLocation'         => 'NONE', // valid values NONE, FRONT, REAR and SIDE
			   'BuildingPartCode'        => 'ROOM', // valid values APARTMENT, BUILDING, DEPARTMENT, SUITE, FLOOR and ROOM
			   'BuildingPartDescription' => $location,
			   'ReadyTimestamp'          => $date."T".$start, //'2008-08-27T13:00:00', Replace with your ready date time
			   'CompanyCloseTime'        => $close
			)
		);

		$response = self::_request('createCourierDispatch', $request);

		if ($response['error'])
		{
			return $request + $response; //for validation purposes this array will evalutate to true
		}

		//Cancel only if we were able to schedule a new one
		if ($donation->pickup_number)
		{
			self::cancel_pickup($donation);
		}

		return self::_return('success', ['pickup_number' => trim($response['success']->DispatchConfirmationNumber), 'date_pickup' => $date_pickup]);
	}


/**
| -------------------------------------------------------------------------
|  Org Pickup
| -------------------------------------------------------------------------
|
| This function is used to create new courrier dispatch for a specified
| account id.  We make a new one using FedEx API
|
|
| @param int account_id
| @param string donor_info
| @param date date_string
| @param date ready_time
| @param date closing_time
| @return array
|
*/

	function org_pickup($org, $start = '', $date = '', $location = '', $contact_name = '', $package_count = '')
	{
		for($i = 0; $i < 5; $i++)
		{
			$window = self::org_window($org);

			if ($window['success']) break;
		}
echo print_r($window, true);

		$date = $date ?: key($window['success']['dates']);

		$start = $start ? strtotime($start) : $window['success']['start'];

		// this works but lots of calls to extend pickup window -> gmdate('H:i:s', $start + $window['success']['window']);
		$close = '16:00:00'; //to simplify things rather than doing window code above lets just setup at 4pm

		$start = gmdate('H:i:s', $start);
		//Convert local pickup time to UTC
		$date_pickup = date::utc("$date $close", DB_DATE_FORMAT, $org->org_id);

		if(ENVIRONMENT != "production")
		{
			return self::_return('success', ['pickup_number' => ENVIRONMENT, 'date_pickup' => $date_pickup]);
		}

		$request = array
		(
			'PackageCount' 			=> $package_count ?: '1',
			'TotalWeight' 				=> ['Value' => '10.0', 'Units' => 'LB'],
			'CarrierCode' 				=> 'FDXG',
			'CourierRemarks' 			=> "SIRUM Box @ $location",
			'OversizePackageCount'	=> '0',
			'OriginDetail' 			=> array
			(
				'PickupLocation' => array
				(
					'Contact' => array
					(
						'PersonName'   => $contact_name ?: $org->user_name,
						'CompanyName'	=> "$org->org_name by SIRUM",
						'PhoneNumber'  => '6504887434' //MUST BE NUMBER e.g. NOT THE FOLLOWING "SIRUM:".PHONE." PICKUP:$org->phone"
					),
					'Address' => array
					(
						'StreetLines' => array
						(
							$org->street
						),
						'City'                => $org->city,
						'StateOrProvinceCode' => $org->state,
						'PostalCode'          => $org->zipcode,
						'CountryCode'         => 'US'
					)
				),
			   'PackageLocation'         => 'NONE', // valid values NONE, FRONT, REAR and SIDE
			   'BuildingPartCode'        => 'ROOM', // valid values APARTMENT, BUILDING, DEPARTMENT, SUITE, FLOOR and ROOM
			   'BuildingPartDescription' => $location,
			   'ReadyTimestamp'          => $date."T".$start, //'2008-08-27T13:00:00', Replace with your ready date time
			   'CompanyCloseTime'        => $close
			)
		);



		$response = self::_request('createCourierDispatch', $request);
echo print_r($request, true);
echo '';
echo print_r($response, true);

		if ($response['error'])
		{
			return $request + $response; //for validation purposes this array will evaluate to true
		}

		return self::_return('success', ['pickup_number' => trim($response['success']->DispatchConfirmationNumber), 'date_pickup' => $date_pickup]);
	}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
