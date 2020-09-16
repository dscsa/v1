<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class defaults
{
	function priorities()
	{
		return array
		(
			'proximity'		 => 4,
			'fairness'		 => 2,
			'request_size'	 => 4,
			'expiration'	 => 6,
			'local_pickup'  => 8,
			'donation_size' => 9,
			'timeframe'		 => 3 * 30 * 24 * 60 * 60
		);
	}

	function org_prefs()
	{
		$all_licenses = serial(self::license());

		$prefs = array
		(
			'match_with_license'	=> $all_licenses,
			'receive_from_license'	=> $all_licenses
		);

		return array_merge($prefs, self::priorities());
	}

	function user_prefs()
	{
		return array
		(
			'email_req_exp'			=> "0#days",
			'email_inv_exp'			=> "0#days",
			'email_req_sat'			=> "0#days",
			'email_inv_sat'			=> "0#days",
			'email_don_rem'			=> "1#months",
			'email_on_match'		=> false,
			'max_emails'			=> 3,
			'per_page'				=> 25
		);
	}

	function services()
	{
		return array
		(
			'Sign up credit',
			'Payment',
			'Single stream'
		);
	}

	 function change_selected()
	{

		return array
		(
			''      						=> 'Please select...',

			'Unrecorded Actions' => array
			(
				'Print'   					=> 'Print Selected',
				'Delete'  					=> 'Delete Selected'
			),

			'Recorded Disposition' => array
			(
				'Received from Donor'    	=> 'Received from Donor',
				'Destroyed - Self'    		=> 'Destroyed - Self',
				'Destroyed - Contractor'    => 'Destroyed - Contractor',
				'Returned to Pharmacy'   	=> 'Returned to Pharmacy',
				'Given to nursing office'	=> 'Given to nursing office',
				'Released to patient'       => 'Released to patient'
			)
		);
	}

	function record_in_disposition()
	{
		return array
		(
			'Received from Donor',
			'Destroyed - Self',
			'Destroyed - Contractor',
			'Returned to Pharmacy',
			'Given to nursing office',
			'Discharge Medication',
			'Pass Medication',
			'Released to patient',
			'Returned to Patient Supply'
		);
	}

	function archive_from_inventory()
	{
		return array
		(
			'Delete',
			'Destroyed - Self',
			'Destroyed - Contractor',
			'Released to patient',
			'Returned to Pharmacy'
		);
	}

	function donor($key = '')
	{
		$type = array
		(
			'' => 'Please Select ...',
			'Federal' =>
			[
				11 => 'Drug manufacturer',
				17 => 'Corporate Partner',
				18 => 'OTC (non-rx) donor'
			],

			'California' =>
			[
				1	=> 'Hospital - general acute care',
				2	=> 'Hospital - acute psychiatric',
				3	=> 'Hospital - chemical dependency recovery',
				4	=> 'SNF - skilled nursing facility',
				5	=> 'ICF - intermediate care facility',
				6 => 'Correctional treatment center',
				7	=> 'Psychiatric health facility',
				8	=> 'RCFE with 16 beds or more',
				9	=> 'MHRC - mental health rehabilitation center',
				10 => 'Pharmacy',
				12 => 'Wholesaler'
			],

			//'California' =>
			//[
			//	21	=> 'Hospital',
			//	22	=> 'SNF - Skilled Nursing Facility',
			//	23	=> 'ICF - Intermediate Care Facility',
			//	24 => 'Correctional Treatment Center',
			//	25	=> 'Psychiatric Health Facility',
			//	26	=> 'RCFE with 16 beds or more',
			//	27	=> 'MHRC - Mental Health Rehabilitation Center',
			//	28	=> 'Pharmacy',
			//	29	=> 'Wholesaler'
			//],

			'Colorado' =>
			[
				30 => 'Prescription Drug Outlet',
				31 => 'Nursing Home',
				32 => 'Assisted Living Residence',
				33 => 'Licensed Practitioner',
				34 => 'Community Mental Health Center'
			],

			'Oregon' =>
			[
				40 => 'Skilled Nursing Facility',
				41 => 'Intermediate Care Facility',
				42 => 'Pharmacy',
				43 => 'Medical Clinic',
				44 => "Licensed Practitioner",
				45 => "Residential Care or Assisted Living Facility",
				49 => "Other Licensed Facility"
			],
			'New Hampshire' =>
			[
				50 => 'Pharmacy',
				51 => 'Licensed Practitioner',
				52 => 'Hospice',
				53 => 'Outpatient clinic',
				54 => 'Nursing home',
				55 => 'Wholesaler',
				56 => 'Correctional facility'
			],

			'Ohio' =>
			[
				60 => 'Pharmacy',
				61 => 'Wholesaler',
				62 => 'Healthcare Facility',
				63 => 'Government Entity'
			],

			'Florida' =>
			[
				70 => 'Pharmacy',
				79 => 'Dept of Children and Families Licensee',
				78 => 'Reverse Distributor'
			],

			'Iowa' =>
			[
				80 => 'General Pharmacy'
			],
			'Pennsylvania' =>
			[
				90 => 'Long-Term Care Nursing Facility',
				91 => "Assisted Living Residence",
				92 => 'Pharmacy',
				93 => 'Wholesaler',
				94 => 'Personal Care Home'
			],
			'Georgia' =>
			[
				100 => 'Nursing Home',
				101 => 'Personal Care Home',
				102 => 'Assisted Living Community',
				103 => 'Pharmacy',
				104 => 'Hospital',
				105 => 'Licensed Practitioner'
			],
			'Washington' =>
			[
				110 => 'Pharmacy'
			],
			'Missouri' =>
			[
				120 => 'Pharmacy'
			],
			'Indiana' =>
			[
				130 => 'Pharmacy'
			],
			'Arizona' =>
			[
				140 => 'Pharmacy',
				141 => 'Wholesaler'
			],
			'Virginia' =>
			[
				150 => 'Pharmacy'
			],
			'Texas' =>
			[
				160 => 'Pharmacy',
				161 => 'Wholesaler'
			],
			'Illinois' =>
			[
				170 => 'Pharmacy'
			],
			'South Carolina' =>
			[
				180 => 'Pharmacy'
			],
			'Maryland' =>
			[
				190 => 'Pharmacy'
			],
			'Maine' =>
			[
				200 => 'Pharmacy'
			],
			'Idaho' =>
			[
				210 => 'Pharmacy'
			],
			'New Mexico' =>
			[
				220 => 'Pharmacy'
			],
			'Nebreska' =>
			[
				230 => 'Pharmacy'
			],
			'Tennessee' =>
			[
				240 => 'Pharmacy'
			],
			'Kentucky' =>
			[
				250 => 'Pharmacy'
			],
			'Massachussets' =>
			[
				260 => 'Pharmacy'
			],
			'Michigan' =>
			[
				270 => 'Pharmacy'
			],
			'New York' =>
			[
				280 => 'Pharmacy'
			],
			'North Carolina' =>
			[
				290 => 'Pharmacy'
			],
			'Wyoming' =>
			[
				300 => 'Pharmacy'
			]
		);

		if ( ! $key)
		{
			return $type;
		}

		//TODO better way to flatten
		$type = $type['Federal'] + $type['California'] + $type['Colorado'] + $type['Oregon'] + $type['New Hampshire'] + $type['Ohio'] + $type['Florida'] + $type['Iowa'] + $type['Pennsylvania'] + $type['Washington'] + $type['Georgia'] + $type['Missouri'] + $type['Indiana'] + $type['Arizona'] + $type['Virginia'];

		return isset($type[$key]) ? $type[$key] : array_search($key, $type);
	}

	function donee($key = '')
	{
		$type = array
		(
			'' => 'Please Select ...',
			'Federal' =>
			[
				17 => 'OTC (non-rx) recipient'
			],
			//'Federal' =>
			//[
			//	1000 => 'OTC (Non-Rx) Recipient'
			//],
			'California' =>
			[
				13	 => 'Pharmacy - county owned',
				14	 => 'Pharmacy - county contracted',
				15	 => 'Pharmacy - primary care clinic',
				16   => 'Primary care clinic - dispensary'
			],

			//'California' =>
			//[
			//	1010	 => 'Clinic - County Owned',
			//	1011	 => 'Clinic - County Contracted',
			//	1012	 => 'Clinic - Dispensary'
			//],
			'Colorado' =>
			[
				1030 => 'Nonprofit Prescription Drug Outlet (PDO)',
				1031 => 'Other Dispensing Outlet (ODO)',
				1032 => "Nonprofit that can possess medication",
				1033 => "Practitioner that can dispense medication"
			],

			'Oregon' =>
			[
				1040 => 'Charitable pharmacy',
				1041 => 'Clinic depot'
			],

			'New Hampshire' =>
			[
				1050 => 'Pharmacy'
			],

			'Ohio' =>
			[
				1060 => 'Pharmacy',
				1061 => 'Hospital',
				1062 => 'Nonprofit Clinic'
			],

			'Iowa' =>
			[
				1081 => 'Wholesaler'
			],

			/*
			pharmacy
			hospital
			wholesaler
			federally qualified health center
			nonprofit clinic
			healthcare facility
			repository
			for: an entity participating in a drug donation or repository program pursuant to another state's law
			healthcare professional
			for: healthcare professional that is otherwise legally authorized to possess prescription drugs
			*/

			'Georgia' =>
			[
				1100 => 'Reverse Distributor',
				1101 => 'Pharmacy',
				1102 => 'Hospital',
				1103 => 'Wholesaler',
				1104 => 'FQHC - Federally Qualified Health Center',
				1105 => 'Nonprofit Clinic',
				1106 => 'Healthcare Facility',
				1107 => 'Repository',
				1108 => 'Healthcare Professional'
			],

			'Tennessee' =>
			[
				1110 => 'Wholesaler'
			],
			'Virginia' =>
			[
				1150 => 'Pharmacy'
			],
			'North Carolina' =>
			[
				1290 => 'Pharmacy'
			]
		);

		if ( ! $key)
		{
			return $type;
		}

		//TODO better way to flatten
		$type = $type['Federal'] + $type['California'] + $type['Colorado'] + $type['Oregon'] + $type['New Hampshire'] + $type['Ohio'] + $type['Iowa'] + $type['Georgia'] + $type['Tennessee'] + $type['Virginia'];

		return isset($type[$key]) ? $type[$key] : array_search($key, $type);
	}

	function color($keys)
	{
		$color =
		[
			'C48323' => 'Black',
			'C48333' => 'Blue',
			'C48332' => 'Brown',
			'C48324' => 'Gray',
			'C48329' => 'Green',
			'C48331' => 'Orange',
			'C48328' => 'Pink',
			'C48327' => 'Purple',
			'C48326' => 'Red',
			'C48334' => 'Turquoise',
			'C48325' => 'White',
			'C48330' => 'Yellow'
		];

		//Multiple colors can be joined with ;
		$keys = explode(';', $keys);

		foreach($keys as $key => $val)
		{
			$keys[$key] = isset($color[$val]) ? $color[$val] : $val;
		}

		return join(' & ', $keys);
	}

	function shape($key)
	{
		$shape =
		[
			'C48335' => 'Bullet',
			'C48336' => 'Capsule',
			'C48337' => 'Clover',
			'C48338' => 'Diamond',
			'C48339' => 'Double Circle',
			'C48340' => 'Freeform',
			'C48341' => 'Gear',
			'C48342' => 'Heptagon',
			'C48343' => 'Hexagon',
			'C48344' => 'Octagon',
			'C48345' => 'Oval',
			'C48346' => 'Pentagon',
			'C48347' => 'Rectangle',
			'C48348' => 'Round',
			'C48349' => 'Semi-Circle',
			'C48350' => 'Square',
			'C48351' => 'Tear',
			'C48352' => 'Trapezoid',
			'C48353' => 'Triangle'
		];

		return isset($shape[$key]) ? $shape[$key] : $key;
	}

	function license($key = null)
	{
		if ($key)
		{
			return self::donor($key) ?: self::donee($key);
		}

		//http://stackoverflow.com/questions/12051782/php-array-merge-recursive-preserving-numeric-keys
		return array_replace_recursive(self::donor($key), self::donee($key));
	}

	function questions()
	{

		$array = array
		(
			"Town of birth",
			"Pet's name",
			"Favorite song",
			"Favorite SIRUM employee",
			"Person you most admire",
			"Favorite musician/band",
			"Favorite actor/actress",
			"Favorite sport",
			"Nickname",
			"Childhood nickname",
			"Favorite TV show",
			"High school mascot",
			"Current kind of car driven",
			"Person you call on phone most",
			"Favorite movie",
			"Favorite airline",
			"Best childhood friend",
			"Favorite relative",
			"Favorite restaurant",
			"Favorite store/shop",
			"First car owned (driven)",
			"Year graduated from high school",
			"Hospital of birth",
			"Cost of first house or apartment",
			"Best friend",
			"Favorite class in high school",
			"Favorite instructor in high school",
			"Favorite neighbor",
			"Favorite type of weather",
			"Favorite color",
			"Favorite holiday",
			"Favorite name",
			"Mother's maiden name",
			"Mother's middle name",
			"Father's middle name",
			"Last 4 digits of SS#",
			"Grade school attended",
			"High School attended",
			"Year of parent's marriage",
			"Favorite dessert",
			"Favorite bank",
			"City where mother was born",
			"City where father was born",
			"Paternal grandmother's maiden name",
			"Maternal grandmother's maiden name",
			"Grandmother's birth month",
			"Grandfather's birth month",
			"Mother's birth month",
			"Father's birth month"
		);

		return array_combine($array, $array);
	}

	function status()
	{
		return array
		(
			''  	   => 'All',
			'pickup'   => 'Pickup',
			'shipped'  => 'Shipped',
			'received' => 'Received',
			'verified' => 'Verified'
		);
	}

	function type()
	{
		return ['medicine' => 'Medicine', 'supply' => 'Supply'];
	}

	function packtype_checkboxes()
	{
		return array
		(
			'PKGCOM' => 'Combination',
			'BOT' => 'Bottle',
			'BLPK' => 'Blisterpack',
			'SYR' => 'Syringe',
			'VIAL' => 'Vial',
			'CRTN' => 'Carton',
			'CTG' => 'Cartridge'
		);
	}

	function type_dropdown()
	{
		return array
		(
			''     => 'Both',
			'N'  => 'Brand',
			'A'  => 'Generic'
		);
	}

	function rx_otc_dropdown()
	{
		return array
		(
			''     => 'Both',
			'R'  => 'Prescription',
			'O'  => 'Over The Counter'
		);
	}

	function method_dropdown()
	{
		$options = self::change_selected();

		return array
		(
			'' => 'Select a method...',
			'Pending' => 'Pending',
			'Donated' => 'Donated'
		) + $options['Recorded Disposition'];
	}

} //END OF CLASS


/* End of file Default.php */
