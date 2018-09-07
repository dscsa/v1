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
			$error_arr = [];
			$num_errors = 0;
			if ((count(inventory::$bulk['alerts']) > 6) || ((count(inventory::$bulk['alerts']) > 1) && (strlen(inventory::$bulk['pharmericaMonth']) == 0)))
			{
				//$v['message'] = html::alert('CSV Errors:<br>'.implode('<br>', inventory::$bulk['alerts']), '', ['style' => 'text-align:left']);
				//fill it with the error rows (with error description in last column)
				$success .= 'Following unique errors:';
				$output = fopen($filepath, 'w');
				for($i = 0; $i < count(inventory::$bulk['alerts']); $i++){
					 $error_text = array_values(array_slice(inventory::$bulk['alerts'][$i], -1))[0];
					if(strpos($error_text,"Donation had to be created") === false){
						if(strpos(strtolower($error_text), "beyond row limit") === false){
							$num_errors += 1;
						}
						fputcsv($output, inventory::$bulk['alerts'][$i]);
					}
					if(!in_array($error_text, $error_arr) AND strlen($error_text) > 0 AND $error_text !== 'error'){
						$success .= '<br>'.$error_text;
						$error_arr[] = $error_text;
					}
				}
				fclose($output);
			}

			if(count(inventory::$bulk['upload']) > 0){
				$success .= '<br><br>The following inventory imported successfully';
			}

			foreach (inventory::$bulk['upload'] as $row => $data)
			{
				//TODO Good enough, but this is not quite right. If the first NDC brings total to -2, then
				//this will goto zero, however then if the next NDC says increase by 1 then it will goto
				//1 and show rather than being at -1 which would still be hidden
				//inventory::increment(['org_id' => $org_id, 'item_id' => $data['id']], $data['dispensed']);

				$success .= "<br>Row $data[row]: $data[ndc] quantity $data[verb] by $data[dispensed]";
			}
			$total_rows = $num_errors + count(inventory::$bulk['upload']);
			$percent_errors = 100 * $num_errors / $total_rows;
			$success .= "<br>".$num_errors."::".count(inventory::$bulk['upload']);
			$success .= "<br> Percentage of errors roughly $percent_errors%";
			$v['message'] = html::info($success, '', ['style' => 'text-align:left']);
			
			if ((count(inventory::$bulk['alerts']) > 6) || ((count(inventory::$bulk['alerts']) > 1) && (strlen(inventory::$bulk['pharmericaMonth']) == 0))){
				//ob_clean();
				//force_download("tmp_import_errors.csv",file_get_contents($filepath)); //use helper function
			}
		}


		if(valid::submit('Get Errors')){
                	ob_clean();
                       force_download("tmp_import_errors.csv",file_get_contents($filepath)); //use helper function

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

