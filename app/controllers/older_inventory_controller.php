<?php

class Inventory_controller extends MY_Controller
{
	function index()
	{
		user::login($org_id);

		$this->load->helper('download');
		$error_filename = "import_errors";
		$filepath = $_SERVER["DOCUMENT_ROOT"].'/'.$error_filename.'.csv';


		if(data::post('delete'))
		{
			inventory::delete(key(data::post('delete')));

			$v['message'] = html::info(text::get('to_default', ['item', 'deleted']));
		}
		else if (valid::submit('add'))
		{
			// if donation_id the we are "adding new" to an existing donation
			inventory::create(['org_id' => $org_id, 'item_id' => data::post('item_id')]);

			//Make sure we don't do a search
			$_POST = [];

			$v['message'] = html::info('Item Added! Enter its quantity.');
		}
		//This else is necessary because POST[delete] is set when add button is pressed
		else if (data::post('edit'))
		{
			$id = key(data::post('edit'));

			//Convert to integer so that a text input won't crash mysql
			$add_qty = +data::post('edit')[$id];

			$verb = +$add_qty > 0 ? 'increased' : 'decreased';

			//This is the same query as donations/_about, make sure to change both
			inventory::increment(compact('org_id', 'id'), $add_qty);

			$add_qty = abs($add_qty);

			log::info("Item $id's quantity was $verb by $add_qty");

			$v['message'] = html::info("Saved! Item's quantity was $verb by $add_qty");
		}
		
		//Submit is programmatic so no button e.g., Search can have been pressed
		//POST's Upload Value will not be set unless form_validation is run.
		if (valid::and_submit('import'))
		{
			item::csv('inventory', 'import');
			$success = '';
			$success = count(inventory::$bulk['alerts']);
			if ((count(inventory::$bulk['alerts']) > 6) || ((count(inventory::$bulk['alerts']) > 1) && (strlen(inventory::$bulk['pharmericaMonth']) == 0))){
				$success .= 'The following errors were found (capped at 100 unique)';
				$output = fopen($filepath, 'w');
				$error_count = 0;
				$error_array = [];
				for($i = 0; $i < min(100,count(inventory::$bulk['alerts'])); $i++){
					fputcsv($output, inventory::$bulk['alerts'][$i]);
					$error_text = array_pop(inventory::$bulk['alerts'][$i]);//array_values(array_slice(inventory::$bulk['alerts'][$i], -1))[0];
					if(($error_text != '') AND ($error_text != 'error') AND (!in_array($error_text,$error_array))){
						$error_count += 1;
						$error_array[] = $error_text;
						$success .= '<br>'.($error_count).' - '.$error_text;
					}
				}
				fclose($output);
				$success .= '<br><br> Click the \'Get Errors\' button to download an error csv';
			}
			if(count(inventory::$bulk['upload']) > 0){
				$success .= '<br><br>The following were imported successfully';
			}

			foreach (inventory::$bulk['upload'] as $row => $data)
			{
					//TODO Good enough, but this is not quite right. If the first NDC brings total to -2, then
					//this will goto zero, however then if the next NDC says increase by 1 then it will goto
					//1 and show rather than being at -1 which would still be hidden
					//inventory::increment(['org_id' => $org_id, 'item_id' => $data['item_id']], $data['dispensed']);

					$success .= "<br>Row $data[row]: $data[ndc] quantity $data[verb] by $data[dispensed]".$data['created_donation'];
			}
			
				
			$v['message'] = html::info($success, '', ['style' => 'text-align:left']);
		}

		//Get rid of anything implicitly deleted
		$this->db->delete('donation_items', '(donor_qty = 0 AND donee_qty IS NULL) OR (donee_qty = 0 AND donor_qty IS NULL) OR (donee_qty = 0 AND donor_qty = 0)');

		$query = result::fields
		(
			['Name', 'item_name', 'item_pop()', 'input()'],
			['', '', '', ['item_desc' => 'input()']],
			['NDC/UPC', 'upc'],
			['Qty', 'donor_qty'],
			[html::div('main_color')->button("donations/start", 'Donate', 'floatright avia-color-theme-color', ['style' => 'width:120px']), '', 'update_inventory()', ['type' => 'radio()']]
		);

		//Hack to temporarily suspend per page limit
		$per_page = result::$per_page;

		result::$per_page = 9999;

		$v['inventory'] = inventory::search(array_merge($query, ['org_id' => $org_id]));

		result::$per_page = $per_page;
		
		//download the error csv that was created by last attempt at import
		if (valid::submit('Get Errors')){
			ob_clean();
			force_download("import_errors.csv",file_get_contents($filepath)); 	
		}

		if (valid::submit('export')) {

			edi::create($v['inventory']);
			//TODO some sort of warning if >500 that says only first 500 exported, export needs to be repeated
			to::info(['Success!', 'Inventory exported successfully']);
		}

		view::full('inventory', 'search inventory', $v);
	}

/**
| -------------------------------------------------------------------------
|  Add
| -------------------------------------------------------------------------
|
| Provides search functionality to the FDA databased allowing user
| to add medicine to their inventory.  Search criteria includes keyword
| (trade, generic), strength, trade, generic, manufacturer, ndc, route of
| administration, Rx or OTC, and package type.
|
| Accessible through the donate link on the top navigation bar
|
| @param int user_id, options
|
*/
  	function add($item_id)
	{
		user::login($org_id);


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

}


/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
