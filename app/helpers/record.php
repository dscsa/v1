<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class record extends MY_Library
{
	//1070
	function status()
	{
		if ( (int) $this->date_verified)
		{
			$title = 'Verified';
			$text  = date::local($this->date_verified);
		}
		else if ( (int) $this->date_received)
		{
			$title = 'Received';
			$text  = date::local($this->date_received);
		}
		else if ( (int) $this->date_shipped)
		{
			$title = 'Shipped';
			$text  = date::local($this->date_shipped);
		}
		else
		{
			$title = 'Pickup';
			$text  = html::link("shipping/pickup/$this->donation_id?iframe=true&lightbox=true", (int) $this->date_pickup ? date::local($this->date_pickup) : 'Schedule', '', ['rel' => 'lightbox']);
		}

		return html::span(html::strong("$title ", '', ['style' => 'color:inherit'])->span($text, 'main_color'), 'nowrap');
	}

	function submit($url = '')
	{
		if(get_object_vars($this))
		{
			if ($url)
			{
				$option = html::button($url, 'Cancel', 'avia-font-color-grey');
			}
			else
			{
				$option = form::submit('Delete');
			}

			return form::submit('Update').$option;
		}

		return form::submit('Create');
	}

	function requests()
	{
		$return = [];

		$details = request::search(['item_group' => $this->item_group, 'org_id' => $this->org_id], 'none');

		foreach ($details as $i => $row)
		{
			$return[] = '<td>'.$row->item_pop('upc', 'item_name').'</td>'.
							'<td style="width:200px">'.$row->bar().'</td>'.
							'<td>'.form::submit('X', '', ['name' => "delete[$row->id]"]).'</td>';
		}

		return '<table class="main_color" style="width: 700px; margin-bottom:0px"><tr>'.implode('</tr><tr>', $return).'</tr></table>';
	}

	//Attempts to use Fontello Icons rather than text
	//av_font_icon avia_animate_when_visible main_color', ['aria-hidden'=>'true', 'data-av_icon'=>'x', 'data-av_iconfont'=>'entypo-fontello', 'style' => 'font-size:20px']
	//<span class="av_font_icon avia_animate_when_visible av-icon-style-border  av-no-color avia-icon-pos-center  avia_start_animation avia_start_delayed_animation" style=""><span class="av-icon-char" style="font-size:20px;line-height:20px;width:20px;" aria-hidden="true" data-av_icon="" data-av_iconfont="entypo-fontello"></span></span>
	//'<div data-av_iconfont="entypo-fontello" data-av_icon="" aria-hidden="true" class="iconbox_icon heading-color"></div>';

	function update_inventory()
	{
		return html::div('floatright')

					  ->div('avia-button avia-color-theme-color-subtle tr-hover', 'Edit', ['data-avia-search-tooltip' => "<input name='edit[$this->id]' type='text' class='inline-block' style='width:70px; margin:6px' autofocus /><input type='submit' value='Add' class='avia-button avia-color-theme-color-subtle' style='margin-right:6px'>"])

					  ->div('main_color inline-block', form::submit('X', 'avia-button tr-hover', ['name' => "delete[$this->id]"]));
	}

	function fedex()
	{
		return html::popup("http://www.fedex.com/Tracking?ascend_header=1&clienttype=dotcom&cntry_code=us&language=english&tracknumbers=$this->tracking_number", $this->tracking_number ?: 'Unknown');
	}

	function edit_user()
	{
		if(data::get('user_id') == $this->user_id)
		{
			return html::link("account/user?iframe=true", 'Edit',  'avia-button avia-color-grey tr-hover', ['rel' => 'lightbox']);
		}

		return null;
	}

	function start_donation()
	{
		return html::div('main_color')->button("donations/confirm/$this->donee_id", 'Donate Here', 'avia-button avia-color-theme-color tr-hover');
	}

	function button($parse, $title, $class = 'avia-color-theme-color-subtle tr-hover', $attributes = [])
	{
		$this->load->library('parser');

		return html::button($this->parser->parse_string($parse, $this, true), $title, $class, $attributes);
	}

	function link($base, $field, $value, $class = '')
	{
		return html::link($base.'/'.$this->$field, $this->$value ?: $value, $class);
	}

	function donor_license()
	{
		return defaults::donor($this->license);
	}

	function na()
	{
		return 'n/a';
	}

	function item_pop()
	{

		$text = "";

		$args = func_get_args();
		//Not sure why default arg doesn't work here
		foreach($args as $arg)
		{
			if ($this->$arg) $text .= $this->$arg.' ';
		}

		//Default in case no args passed
		$this->load->helper('text');

		$text = ellipsize($text ?: $this->item_name, 80, 1);

		$tt = html::div('inline-block', '', ['style' => 'vertical-align:top'])

					->strong($this->type.' Name')->br()

						->add($this->item_name)->br()

						->add($this->item_desc)->br()

					->strong('Details')->br()

						->add('NDC: '.$this->upc)->br()

						->add($this->mfg)->br()

					->strong('Price/Unit')->br()->add($this->price > 0 ? $this->price.' ('.($this->price_type ?: 'N/A').')' : 'Not Available');

		//$tt is going inside the tooltip attribute.  We need to escape any quotes, however addslashes doesn't seem to work e.g., Tom's for NDC: 510090830
		$tt = str_replace("'", "", $tt);

		$pic = html::div('inline-block', $this->image(200).$this->caption(),  ['style' => 'line-height:2em;font-size:12px;']);

		return "<div style='cursor:pointer; display:inline-block' data-avia-tooltip='$tt$pic'>{$text}</div>";

		return html::div('inventory/'.$this->item_id, $text, 'tt nowrap', ['ttclass' => 'ttmedicine']);
	}

	function item_desc()
	{
		$this->load->helper('text');

		return ellipsize($this->item_desc, 30, 1);
	}

	function checkbox($field, $value = null)
	{
		return form_checkbox(['name' => $field.'[]', 'value' => $this->$value ?: $this->$field, 'checked' => set_value($field.'[]', $this->$field)]);
	}

	//Use - as placeholder so its easy to update with actual quantity
	function donor_qty_input()
	{
		if ($this->donation_type == 'Request')
		{
			return  $this->donor_qty;
		}

		//This must be called before qty_input for tabindex to work properly
		$exp_date = self::exp_date('donor');

		$input    = self::qty_input("items[$this->id][donor_qty]", $this->donor_qty);

		$hidden   = form::hidden("items[$this->id][id]", $this->id);

		$hidden  .= form::hidden("olds[$this->id][item_id]", $this->item_id);

		return "$input $exp_date$hidden";
	}

	// =     : same as donor qty (donee only)
	// blank : unknown quantity (null in the database)
	// 0     : none i.e., known quantity of zero
	// >0    : actual quantity recorded

	//Use - as placeholder so its easy to update with actual quantity
	function donee_qty_input()
	{
		$qty   = $this->donee_qty;
		$state = data::get('state');

		if ($this->donation_type == 'Donation')
		{
			return $qty;
		}

		$checked = ! (int) $this->archived;

		//A little weird.  Most times we want NUll to equal blank
		//however in the case that a received donation is being
		//verified by donee for the first time, the donee will have null qty
		//but we want the default save to be "verify all quantities as correct"
		//therefore the inputs in this case should default to "="
		//so that verifying the donation confirms the quanties.
		//after this inital verification, then the inputs should go
		//back to having NULL equal "-"
		if ($qty == null AND ! (int) $this->date_verified AND $this->donor_qty)
		{
			$qty = '=';

			if ('CO' == $state || 'OH' == $state)
				$checked = true;
		}

		//This must be called before qty_input for tabindex to work properly
		$exp_date = self::exp_date('donee');

		$lot      = self::lot_number('donee');

		$quantity = self::qty_input("items[$this->id][donee_qty]", $qty);

		$accept   = form_checkbox("items[$this->id][archived]", '0000-00-00 00:00:00', $checked);

		$hidden   = form::hidden("olds[$this->id][donee_qty]", $qty);

		$hidden  .= form::hidden("olds[$this->id][archived]", $this->archived);

		$hidden  .= form::hidden("olds[$this->id][item_id]", $this->item_id);

		$hidden  .= form::hidden("olds[$this->id][donor_qty]", $this->donor_qty);

		return "$quantity $lot $exp_date $accept$hidden";
	}

	function exp_date($type)
	{
		$state = data::get('state');

		if ('CA' == $state OR 'OH' == $state)
			return '';

		if ('OR' == $state AND 'donor' == $type)
			return '';

		if ('CO' == $state AND 'donee' == $type)
			return '';

		//This needs to come before qty_input call so that self::autofocus will still be true
		return form_input(['name' => "items[$this->id][exp_date]", 'value' => date::format($this->exp_date, 'm/y'), 'class' => 'narrow check_row inline-block', 'placeholder' => 'mm/yy', 'tabindex' => self::$autofocus ? 2 : 0]);

	}

	function lot_number($type)
	{
		//With OR lot_number waiver, no one currently uses this.
		return '';

		$state = data::get('state');

		if ('OR' != $state OR 'donee' != $type) {
			return '';
		}

		return form_input(['name' => "items[$this->id][lot_number]", 'value' => $this->lot_number, 'class' => 'inline-block', 'style' => 'width:80px;', 'placeholder' => 'lot#', 'tabindex' => self::$autofocus ? 2 : 0]);
	}

	function dollar($field)
	{
		return text::dollar($this->$field);
	}

	function qty_donate()
	{
		//3 Scenarios for adding medicine to donation
		//1. Donor creates a new donation    -> default to total inventory for requested.  blank for Unrequested
		//2. Donor/Donee adds a new item     -> default to total inventory for ones just added, zero for rest
    //3. Donor/Donee adds a current item -> default to zero quantity (But this is actually the same as 2)

		//If set does not work because intentional '0' is falsy
		if (empty($_POST['quantities']) OR $_POST['quantities'][$this->id] === null)
			$default = ($this->requested !== null OR data::get('state') == 'OH' OR data::get('state') == 'CA') ? $this->donor_qty : 0;
		else
			$default = $_POST['quantities'][$this->id];

		return self::qty_input('quantities['.$this->id.']', $default); //we don't want deleteonfocus here
	}

	// only want first input box to have a tab index of 1 so that on a tab browser will go
	// to the input search box which has a tab index of 2.  0 is the lowest priority
	static $autofocus = 'autofocus';

	// Qty has 4 possible types
	function qty_input($name, $default = '', $placeholder = '', $class = '')
	{
		$value = set_value($name, $default);

		$class .= ' check_row narrow inline-block';

		//http://stackoverflow.com/questions/308122/simple-regular-expression-for-a-decimal-with-a-precision-of-2
		//replaced ']d+' with '{1,2}' so the maximum input value is 500 (we don't want to save miss-entries)
		if ( ! preg_match('/^([1-4][0-9]?[0-9]?|[5-9][0-9]?)(\.\d*)?$|^\.\d+$|^=$/', $value))
		{
			$class .= ' red';
		}

		$return = form_input(compact('name', 'class', 'value') +  ['placeholder' => $placeholder, 'tabindex' => !!self::$autofocus, self::$autofocus => true]);

		self::$autofocus = false;

		return $return;
	}

	function text($field, $class = '', $attributes = ['rows' => 5])
	{
		return form::text($field, $this->$field, $class, $attributes);
	}

	function input($field, $class = '')
	{
		return form::input($field, $this->$field, $class);
	}

	function dropdown($field, $options, $class = '', $attributes = [])
	{
		return form::dropdown($field, $this->$field, $options, $class, $attributes);
	}

	function date($field, $format = '', $default = '')
	{
		return ((int) $this->$field) ? date::local($this->$field, $format) : $default;
	}

	function exp($field)
	{
		$date = $this->date($field);

		if ($date != DEFAULT_USER_DATE && date_create($date) < date_create())
		{
			return "<div class='red'>$date</div>";
		}

		return $date;
	}

	function decrypt($field)
	{
		return secure::decrypt($this->$field);
	}

	function requested()
	{
		return $this->requested !== null ? html::image('icons/star.png') : '';
	}

	function image($size)
	{	//Default Width
		$width = $size ?: 140;

		if ($this->image)
		{
			//Zoom in to fixed width and crop the image
			//imagescdn does not support https, set to http
			$img = html::image('http:'.$this->image, 'width:'.$width.'px; margin-bottom:-10px; margin-left:-5px'); //'margin-top:-'.($width / 25).'px; margin-left:-'.($width / 25).'px;

			//Crop the image height to be the same as width
			$style = 'height:'.$width*.65.'px;  width:'.($width - 30).'px; display:table-cell; vertical-align:middle; white-space:normal';
		}
		else
		{
			 $this->load->helper('text');

			$img = $size ? '' : $this->caption(false);

			$style = 'height:'.$width*.65.'px;  width:'.($width - 30).'px; display:table-cell; vertical-align:middle; white-space:normal';
		}

		return html::div('', $img, ['style' => $style]);
	}

	function caption($shape = true)
	{
		if ($shape)
		{
			$shape  = ', '.$this->shape;

			$shape .= $this->size ? ', '.$this->size.'mm' : '';
		}

		return $this->imprint ? "$this->imprint - $this->color$shape" : '';
	}

	function partner()
	{
		$this->load->helper('text');

		if (empty($this->donor_id))
		{
			$type = '';
		}
		else if(data::get('org_id') == $this->donor_id)
		{
			$type = 'donee_';
		}
		else
		{
			$type = 'donor_';
		}

		$org_id = $this->{ ($type ?: 'org_') .'id'};

		$name = ellipsize($this->{ $type ? $type.'org' : 'org_name' }, 30, 1);

		$tt = html::div('inline-block', '', ['style' => 'margin:10px; vertical-align:top'])

					->strong($name)->br()

						->add($this->{$type.'license'} ? defaults::license($this->{$type.'license'}) : 'None')->br()

					->strong('Contact')->br()

						->add($this->{ $type ? $type.'user' : 'user_name' })->br()

						->add($this->{$type.'email'})->br()

						->add($this->{$type.'phone'})->br()

					->strong('Address')->br()

						->add($this->{$type.'street'})->br()

						->add($this->{$type.'city'}.', '.$this->{$type.'state'}.' '.$this->{$type.'zip'});

			//$tt is going inside the tooltip attribute.  We need to escape any quotes, however addslashes doesn't seem to work
			$tt = str_replace("'", "", $tt).$this->small_pic($org_id);

		return "<div class='red' style='cursor:pointer; display:inline-block' data-avia-search-tooltip='$tt"."'>$name</div>";
	}

	function large_pic($id = '')
	{
		$id = $id ?: $this->org_id;

		return html::image(file::exists('images', "org/$id.jpg", 'default/'.($id % 4).'.jpg'));
	}

	function small_pic($id = '')
	{
		$id = $id ?: $this->org_id;

		return html::image(file::exists('images', "org/$id.jpg", 'default/'.($id % 4).'.jpg'), 'height:200px; margin-top:5px');
	}

	function agreement_links()
	{
		$links = '<div>';
		if ($this->date_donor > 0) $links .= html::link("/doc/SIRUM - DONOR USER AGREEMENT.pdf?iframe=true", 'Donor agreement', '', ['rel' => 'lightbox']).' effective '.date::local($this->date_donor, 'F Y');
		if ($this->date_donor > 0 && $this->date_donee > 0) $links .= '<br>';
		if ($this->date_donee > 0) $links .= html::link("/doc/SIRUM - DONEE USER AGREEMENT.pdf?iframe=true", 'Donee agreement', '', ['rel' => 'lightbox']).' effective '.date::local($this->date_donee, 'F Y');
		if ($this->date_donor == 0 && $this->date_donee == 0) $links .= 'Neither as a '.html::link('join/update/donor?to=account', 'donor'). ' nor a '.html::link('join/update/donee?to=account', 'donee');
		$links .= '</div>';

		return $links;
	}

	function concat()
	{
		$args = func_get_args();

		foreach($args as $arg)
		{
			$return[] = $this->$arg;
		}

		return implode(', ', $return);
	}

/**
| -------------------------------------------------------------------------
|  Bar
| -------------------------------------------------------------------------
|
| This function shows the percent of the amount of the request fulfilled as
| a graphical bar
|
| @param int quantity remaining, amount remaining
| @param into quantity total, orignial amount requested
|
*/

	function bar($field = 'quantity', $max = 'original')
	{
		$percent = round(max(min($this->$field/max($this->$max, 1), 1), 0) * 100);

		if ($this->$field > 1000) $this->$field = round($this->$field/1000).'k';
		if ($this->$field == 0)
		{
			$percent      = 100;
			$this->$field = 'N/A';
		}

		//147px allows quantity of 100k to show without wrapping
		return 	"<div class='avia-progress-bar theme-color-bar icon-bar-no'>
						<div class='progressbar-title-wrap'>
							<div class='progressbar-icon'>
								<span class='progressbar-char' aria-hidden='true' data-av_icon='' data-av_iconfont='entypo-fontello'></span>
							</div>
							<div class='progressbar-title'>{$this->$field} ($percent%)</div>
						</div>
						<div class='progress avia_start_animation'>
							<div class='bar-outer'>
								<div class='bar' style='width: {$percent}%' data-progress='$percent'></div>
							</div>
						</div>
					</div>";
	}




} //End of class





/* ------------------------------------------------------------------------- End of File -------------------------------------------------------------------------*/
