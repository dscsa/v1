<?php
class medicine
{

/**
| ------------------------------------------------------
|  UPC: format barcode/NDC as UPC for used in item table
| ------------------------------------------------------
| @param barcode, large integer or - (dash) delimited NDC
| @note run statically from where
| @return the correct 9 digit UPC
| Converts a FDA's dashed NDC format into a UPC using Medicaid's
| 9 digit standard and look for common barcode formats that add
| leading or trailing digits to the UPC
*/
   function upc($barcode)
   {
		if ( ! $barcode) return null;

		$barcode = trim($barcode);

		//Is it delimited? Labeler Code should be 5 digits and Product code should be 4 digits.
		if (count($explode = explode('-', $barcode)) > 1)
		{
			//if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode is delimited");
			return str_pad($explode[0], 5, '0', STR_PAD_LEFT).str_pad($explode[1], 4, '0', STR_PAD_LEFT);
		}

		//Check for Code 128 Barcode (can be any length but has pretermined beginning 5 digits
		if (substr($barcode, 0, 5) == '01003')
		{
			//if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode is in Code 128 format");
			return substr($barcode, 4, 10);
		}

		//Pharmerica uses UPC-A barcode format adding one digit before (3) and one after (checksum)
		if (substr($barcode, 0, 1) == '3' and strlen($barcode) == 12)
		{
			//Uses 10 digit NDC.  Padding can be on mfg, prod, or pkg.  So if the mfg and prod don't
			//have padding then it must be included in the package.  Examples:
			//360505257997 -> 60505-2579-9 Lipitor 20 mg (this one is tough since 1 digit package code)
			//366685100115 -> 66685-1001-1 Amox - TR-K (this one is tough since 1 digit package code)
			//300080836224 -> 0008-0836-22 Effexor 150mg
			//301439898054 -> 0143-9898-05 Cephalexen 250mg
			//303780023055 -> 0378-0023-05 Diltiazem 30mg
			//367877198057 -> 67877-0198-05 Amlodipine 5mg
			//367877198903 -> 67877-0198-90 Amlodipine 5mg
			//if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode is in Pharmerica format");
			$barcode = substr($barcode, 1, 10); // handled later by adding padding for extra digit
		}

		//Omincare adds two extra digits to the end of the ndc
		if (strlen($barcode) == 12)
		{
			//if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode is in Omnicare format");
			$barcode = substr($barcode, 0, 10);
		}

		//An EAN 13 barcode
		if (substr($barcode, 0, 2) == '03' and strlen($barcode) == 13)
		{
			//if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode is in EAN format");
			return substr($barcode, 2, 10);
		}

		//Hopefully in the correct 9 or 11 digit format already
		if (strlen($barcode) == 9 or strlen($barcode) == 11)
		{
			//if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode in exact 9 or 11 digit format");
			return substr($barcode, 0, 9);
		}

		//Probably 5-4-1, 4-4-2, or 5-3-2 format.  Try as is and with an additional 0 to make a full NDC9
		if (strlen($barcode) >= 8)
		{
			//if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode needed one digit padding");
			return ['0'.substr($barcode, 0, 8), substr($barcode, 0, 5).'0'.substr($barcode, 5, 3), substr($barcode, 0, 9)];
		}

		//An incomplete NDC
		if ( ! \data::get('admin_id')) \log::info("Barcode: $barcode needed wildcards");
		return "%$barcode%";
	}

	function ndc($cols)
	{
		list($id, $ndc, $rx_otc, $name, $suffix, $generic, $form, $route, $start, $end, $app_type, $app_num, $mfg, $substance, $strength, $unit, $class, $controlled) = $cols;

		//skip column headings
		if ($id == 'PRODUCTID') return;

		//skip controlled/scheduled drugs,
		if ($controlled) return;

		//skip unapproved drugs
		if (substr($app_type, 0, 10) == 'UNAPPROVED') return;

		//skip sunscreen drugs
		if (preg_match('/sunscreen/i', $name)) return;

		//skip non-rx/otc/vaccine
		if ($rx_otc == 'HUMAN OTC DRUG')
		{
			$rx_otc = 'OTC';
		}
		else if ($rx_otc == 'HUMAN PRESCRIPTION DRUG')
		{
			$rx_otc = 'Rx';
		}
		else if ($rx_otc == 'VACCINE')
		{
			$rx_otc = 'Vaccine';
		}
		else
		{
			return;
		}

		list($label, $prod) = explode('-', $ndc);

		$mfg = explode(' ', strstr($mfg, ',', true) ?: $mfg, 3);

		//collate strengths with units and get rid of the /1 followed by a semicolon or EOL that the FDA uses to say 'each' while still leaving /10, /100, /1000 etc.
		$dosage = preg_replace(['#/1(;|$)#', '#=#'], '', urldecode(http_build_query(array_combine(explode('; ', $strength), explode('; ', $unit)), '', '; ')));

		$brand = ($app_type == 'NDA' or $app_type == 'BLA') ? 'Brand' : 'Generic';

		$spl = explode('_', $id)[1];

		$save =
		[
			'updated'      => "'".gmdate(DB_DATE_FORMAT)."'",
			'type'			=> "'medicine'",
			'name' 			=> "'".addslashes(str_replace(' And ', ' and ', ucwords(strtolower($generic))).strtolower(", $dosage $form"))."'",
			'description'	=> "'".addslashes(str_replace(' And ', ' and ', ucwords(strtolower("$name $suffix")))."($rx_otc $brand)")."'",
			'upc' 			=> "'".str_pad($label, 5, '0', STR_PAD_LEFT).str_pad($prod, 4, '0', STR_PAD_LEFT)."'",
			'url'				=> "'http://www.accessdata.fda.gov/spl/data/$spl/$spl.xml'",
			'mfg' 			=> "'".addslashes(ucwords(strtolower(isset($mfg[1]) ? "$mfg[0] $mfg[1]" : $mfg[0])))."'",
		];

		$insert = urldecode(http_build_query($save, '', ', '));

		$this->db->query("INSERT INTO item SET $insert ON DUPLICATE KEY UPDATE $insert");

		if ($this->db->affected_rows() == 1)
		{
			log::info("Inserted $save[upc]");
		}
		else
		{
			log::info("Updated $save[upc]");
		}
	}

	function image($cols)
	{
		list($id, $upc, $origin, $url) = $cols;

		sleep(.1); //otherwise drugs.com thinks its a DOS attack and will ban IP for 24 hours

		$upc = $origin ?: $upc;

		$file = \item::curl("http://www.drugs.com/imprints.php?action=search&ndc=$upc");

		$item = [];

		//Because of wildcards, drugs.com search sometimes gives wrong results for delimited ndcs
		//providing a padded one (ie upd) seems to prevent this problem (0009-0011, 0049-0052)
		//so we need to find the correct NDC listed on the page in order to accept the results
		if (preg_match_all('~[^"\']+/images/pills/[^"\']+~', $file, $match))
		{
			\log::info("Drugs.com has image for $upc");

			$item['image'] = $match[0][1];
		}
		else
		{
			\log::info("Drugs.com has NO image for $upc");
			//TODO get this work with ndc if at all
			//$file = \item::curl("http://pillbox.nlm.nih.gov/PHP/pillboxAPIService.php?key=7SETYPBTYS&prodcode=$item[ndc]");
			//
			//$xml = simplexml_load_string($file, 'SimpleXMLElement',  LIBXML_NOERROR | LIBXML_NOWARNING);
			//
			//if ($xml && $xml->pill && (array) $xml->pill->HAS_IMAGE)
			//{
			//	\log::info("Pillbox has image for $item[ndc]");
			//
			//	$item['image'] = "'http://pillbox.nlm.nih.gov/assets/small/".$xml->pill->image_id.".jpg'";
			//}
		}

		return $item;
	}

	function imprint($cols)
	{
		list($id, $upc, $origin, $url) = $cols;

		$spl = simplexml_load_string(\item::curl($url), 'SimpleXMLElement',  LIBXML_NOERROR | LIBXML_NOWARNING);

		//Sometimes FDA doesn't have the label availble
		if ( ! $spl or ! $spl->component->structuredBody->component->section->subject)
		{
			return;
		}

		$item = [];

		foreach($spl->component->structuredBody->component->section->subject as $med)
		{
			$med = $med->manufacturedProduct;

			list($label, $prod) = explode('-', $med->manufacturedProduct->code['code']);

			if ($upc == str_pad($label, 5, '0', STR_PAD_LEFT).str_pad($prod, 4, '0', STR_PAD_LEFT))
			{
				foreach ($med->subjectOf as $key)
				{
					$key = $key->characteristic;

					switch($key->code['code'])
					{
						case 'SPLCOLOR':
							$item['color'] = ucfirst(strtolower($key->value['displayName'])); break;
						case 'SPLSHAPE':
							$item['shape'] = ucfirst(strtolower($key->value['displayName'])); break;
						case 'SPLSIZE':
							$item['size'] = $key->value['value'].$key->value['unit']; break;
						case 'SPLSCORE':
							$item['score'] = $key->value['value']; break;
						case 'SPLIMPRINT':
							$item['imprint'] = addslashes($key->value); break;
					}
				}

				if ($origin = $med->manufacturedProduct->asEquivalentEntity)
				{
					list($label, $prod) = explode('-', $origin->definingMaterialKind->code['code']);

					$item['upc_origin'] = str_pad($label, 5, '0', STR_PAD_LEFT).str_pad($prod, 4, '0', STR_PAD_LEFT);
				}
			}
		}

		return $item;
	}

	function price($cols)
	{
		list($name, $upc, $nadac, $date) = $cols;

		if ( ! is_numeric($upc)) return;

		//Remove package code and pad any missing digits
		$upc = str_pad(substr($upc, 0, -2), 9, '0', STR_PAD_LEFT);

		$save =
		[
			'price' 		 => $nadac,
			'price_date' => date(DB_DATE_FORMAT, strtotime($date)),
			'price_type' => "nadac",
			'updated'	 => gmdate(DB_DATE_FORMAT)
		];

		$this->db->where('upc', $upc);

		$this->db->or_where('upc_origin', $upc);

		$this->db->update('item', $save);
	}

}  // END OF CLASS
