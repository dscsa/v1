<?php
class Pdf
{
	/**
	| -------------------------------------------------------------------------
	| Manifest
	| -------------------------------------------------------------------------
	|
	| This function is creates a formatted PDF of the packing slip
	|
	| @param array trans_info, options
	| @param array from_profile, options
	| @param array to_profile, options
	|
	*/
		function manifest($filePath, $donation)
		{
			$donation[0]->date_donated = $donation[0]->date('date_status', "Y-m-d");

			$donation[0]->order_number = text::between($filePath);

			$donation[0]->donation_value = text::dollar($donation->sum('value'));

			$data = (array) $donation[0];

			$data['items'] = (array) $donation;

			return self::reportingCloud($filePath, $data, 'manifest_reporting_cloud.doc');
		}

		/**
		* Taken and modified from https://github.com/TextControl/txtextcontrol-reportingcloud-php/blob/master/src/ReportingCloud.php
		* Merge data into a template and return an array of binary data.
		* Each record in the array is the binary data of one document
		*
		* @param array   $mergeData        Array of merge data
		* @param string  $returnFormat     Return format
		* @param string  $templateName     Template name
		* @param string  $templateFilename Template filename on local file system
		* @param boolean $append           Append flag
		* @param array   $mergeSettings    Array of merge settings
		*
		* @throws InvalidArgumentException
		*
		* @return null|string
		*/

	 function reportingCloud($filePath, $data, $templateName, $settings = null, $returnFormat = 'PDF')
	 {
		 $json = [
				 'mergeData' => [$data],
				 'mergeSettings' => $settings
		 ];

		 $body = self::http('POST', "https://api.reporting.cloud/v1/document/merge?templateName=$templateName&returnFormat=$returnFormat", $json);

		 if ( ! is_array($body) || ! count($body)) {
			 return self::_return('error', log::error("Reporting Cloud had a problem".print_r($body, true)));
		 }

		 $this->load->helper('file');

		 if ( ! write_file($filePath, array_map('base64_decode', $body)[0]))
 	 	 {
			 return self::_return('error', log::error("PDF Library could not write file"));
 	 	 }

 		 return self::_return('success', "PDF Library created $filePath");
	}

	function http($method, $uri, $jsonBody = null)
	{
		$curl = curl_init($uri);

		if (strtolower($method) == 'post')
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($jsonBody));

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD, "info@sirum.org:!" . secure::key('pdf_key'));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		$result = curl_exec($curl);

		log::info("HTTP ".print_r($result, true));

		curl_close($curl);

		return json_decode($result);
	}



	function _return($type, $msg)
	{
		return array_merge(['error' => false, 'success' => false], [$type => $msg]);
	}

/**
| Merge
| 
| Takes a 2D array of batches of filenames. Each batch (one of the 1D arrays) will get collated
| and between each batch it adds some blank pages so it can all be printed out together
| while making it easy to separate.
**/

	function merge($full_arr)
	{
		require_once('../app/libraries/pdf/fpdi.php');
		$pdf = new FPDI('P', 'mm', 'Letter');
                $filename = "merged_".date("m_d_Y_h_i").".pdf";
		$filepath = file::path('label', $filename);
		
		foreach($full_arr as $row => $batch){
			//ADD 6 BLANK PAGES so that people in office can separate batches
			$pdf->AddPage('P');
                        $pdf->AddPage('P');
                        $pdf->AddPage('P');
			$pdf->AddPage('P');
                        $pdf->AddPage('P');
                        $pdf->AddPage('P');
			foreach($batch as $row => $file){
				$pdf->setSourceFile(file::path('label',$file));
				for($i = 1; $i <= 2; $i++){
					$pdf->AddPage('P');
					$tpl = $pdf->importPage($i);
					$pdf->useTemplate($tpl);
				}
			}
		}
		$pdf->Output($filepath, "F");
		return $filename;
	}


/**
| -------------------------------------------------------------------------
| Label
| -------------------------------------------------------------------------
|
| This function is creates a formatted PDF of the label
|
| @param array trans_info, options
| @param array from_profile, options
| @param array to_profile, options
|
*/


	function label($file, $donation, $tracking)
	{
		require_once('../app/libraries/pdf/fpdi.php');

		$pdf = new FPDI('P', 'mm', 'Letter');

		$pdf->SetAutoPageBreak(false);

		$pdf->AddPage();

		$pdf->Image("$file.png", 11, 125, -200);

		unlink("$file.png");

		//ignore pagecount for now since we know each types pagecount
		$pagecount = $pdf->setSourceFile(file::path('template', "Donation Coversheet.pdf"));

		$tmpl = $pdf->importPage(1);

		$pdf->useTemplate($tmpl, 0, 0);

		$pdf->SetLeftMargin(12);

		//Put in the static information right above the confidentiality agreement
		$pdf->SetFont('Helvetica', '', 10);

		$pdf->SetTextColor(64);

		$pdf->SetXY(123, -64);

		$pdf->MultiCell(0, 4, "Tracking #$tracking - ".text::between($file)."\n\n$donation->donor_org\n$donation->donor_street, $donation->donor_city, $donation->donor_state $donation->donor_zip\n\n$donation->donee_org\n$donation->donee_street, $donation->donee_city, $donation->donee_state $donation->donee_zip");

		//Insert the dynamic donor and donee instructions
		//We have to convert our mini-syntax into PDF formatting
		$pdf->SetY(24);

		//Trim is in case no donee instruction, then we want the donor instruction to have its first line as the title ($i == 0 a few lines below).
		$split = str_replace(["\n", "\n\n\n\n", "*\n", "\n[]"], ["\n\n", "\n\n\n", "*", "[]"], trim("$donation->donee_instructions\n$donation->donor_instructions"));
		$split = preg_split('/\n|(\*|\[\])/', $split, -1, PREG_SPLIT_DELIM_CAPTURE);

		$out  = '';
		$bold = false;

		foreach ($split as $i => $line)
		{
			//First line is the heading
			if ($i == 0)
			{
				$pdf->SetFont('', 'B', 20);

				$pdf->SetTextColor(68, 200, 245);

				$pdf->Write(5, $line);

				$pdf->SetFont('', '', 11);

				$pdf->SetTextColor(64);

				continue;
			}

			if ($line == '*')
			{
				$pdf->SetFont('', $bold = !$bold ? 'B' : '');

				continue;
			}

			if ($line == '')
			{
				$pdf->Ln(5);

				continue;
			}

			//debug(ord("▢"));
			//die();

			//iconv($myencodig, 'windows-1252', trim(utf8_decode($str)));
			if ($line == '[]')
			{

				$pdf->SetFont('ZapfDingbats', '', 20);

				$pdf->Write(5, chr(113));

				$pdf->SetFont('Helvetica', '', 11);

				continue;
			}

			$pdf->Write(5, mb_convert_encoding($line, 'windows-1252'));
		}

		//Template must be two pages.  The second page should be blank.
		$pdf->AddPage();

		$pdf->useTemplate($pdf->importPage(2), 0, 0);

		//We insert SIRUM's address in the top left
		$pdf->SetXY(14, 33);
		//Double quotes important.  FPDF won't recognize new line character \n with single quotes
		$pdf->MultiCell(0, 5, preg_replace('/, /', "\n", ADDRESS, 1));

		//We insert donor's address in the midle of the page (for mailing in clear envelopes)
		$pdf->SetFont('Helvetica', 'B', 14);

		$pdf->SetXY(70, 60);

		$pdf->MultiCell(0, 6, "$donation->donor_org\nAttn: $donation->donor_user\n$donation->donor_street\n$donation->donor_city, $donation->donor_state $donation->donor_zip");

		$pdf->Output($file, "F");
	}

/*
| -------------------------------------------------------------------------
| Helper Functions
| -------------------------------------------------------------------------
| This section contains helper functions to support the user called functions above
|
| Because helper functions should not be called by the user directly, put an
| underscore "_" before each function name.  For example, function _helper_function()
|
*/

/**
 * Convert a PHP assoc array to a SOAP array of array of string
 *
 * @param array $assoc
 * @return array
 */
function assocArrayToArrayOfArrayOfString ($assoc)
{
	//if first element is object or array then we have a 2d
	if ( ! is_string(reset($assoc))) $assoc = reset($assoc);

	return array ( array_keys( (array) $assoc), array_values( (array) $assoc));
}

/**
 * Convert a PHP multi-depth assoc array or array of objects to a SOAP array of array of array of string
 *
 * @param array $multi
 * @return array
 */
function multiAssocArrayToArrayOfArrayOfString ($multi)
{
		$arrayKeys   = [0 => array_keys( (array) $multi[0])];

		foreach ($multi as & $row)
		{
			$row = array_values((array) $row);
		}

		return array_merge($arrayKeys, (array) $multi);
}

}
/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
