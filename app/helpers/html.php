<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class html extends MY_Helper
{
	protected $tags = [];

/**
| -------------------------------------------------------------------------
| Trigger: close any open tags before displaying
| -------------------------------------------------------------------------
| @note Overrides MY_Helpers __toString method
| @return string built by each method in the chain
*/
	function __toString()
	{
		foreach($this->tags as $tag)
		{
			$this->string .= $tag;
		}

		return $this->string;
	}

/**
| -------------------------------------------------------------------------
| Default: if function not defined, create a tag named after the function
| -------------------------------------------------------------------------
| @note uses call magic method rather than defining every possible html tag
| @return chainable object
*/
	function __call($name, $args)
	{
		if ( ! method_exists($this, $name))
		{
			array_unshift($args, $name);

			$name = 'tag';
		}

		return call_user_func_array([$this, $name], $args);
	}

/**
| -------------------------------------------------------------------------
| Tag: creates an opening and closing html tag with specified attributes
| -------------------------------------------------------------------------
| @param $tag, name of the tag to create, e.g., p for <p></p>
| @param $value, value between tags, if empty then stores closing tag which
| will be closed automatically by the trigger or manually with the end method
| @param $class, string containing css classes to assign the tag
| @param $attributes, array of other attributes to add to the tag
| @return chainable object
*/
	protected function tag($tag, $value = '', $class = '', $attributes = [])
	{
		$this->string .= "\n<$tag "._parse_attributes($attributes + compact('class')).">";

		if ($value || $value === 0.0)
		{
			$this->string .= "$value</$tag>";
		}
		else
		{
			array_unshift($this->tags, "</$tag>");
		}

		return $this;
	}


/**
| -------------------------------------------------------------------------
| Form: wrapper that creates both the form::open and form::close tags
| -------------------------------------------------------------------------
| @param $submit, optionally add a submit button to the top of the form
| @return chainable object
*/
	protected function form($action = '', $attributes = [], $hidden = [])
	{
		$this->string .= form::open($action, $attributes, $hidden);

		array_unshift($this->tags, form::close());

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Submit: wrapper that creates a form::submit button
| -------------------------------------------------------------------------
| @return chainable object
*/
	protected function submit($value, $class = '', $attributes = [])
	{
		$this->string .= form::submit($value, $class, $attributes);

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Nbs: create $num no-break spaces
| -------------------------------------------------------------------------
| @return chainable object
*/
	protected function nbs($num = 1)
	{
		$this->string .= str_repeat('&nbsp;', $num);

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Br: create $num html breaks
| -------------------------------------------------------------------------
| @return chainable object
*/
	protected function br($num = 1)
	{
		$this->string .= str_repeat("\n<br />", $num);

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Hr: create a new section with hr line and an optional <h3>title</h3>
| -------------------------------------------------------------------------
| @param title, optional <$elem>string</$elem> to accompany hr
| @param class, optional class for title
| @param elem, optional element for title
| @return chainable object
*/
	protected function hr($title = '', $class = '', $elem = 'h3')
	{
		$this->string .= "\n<hr />";

		if ($title)
		{
			$this->string .= "\n<$elem class='$class'>$title</$elem>";
		}

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Toggle: div using jquery to toggle visibilty on click. degrades gracefully
| -------------------------------------------------------------------------
| @param $value, the contents to insert into the div
| @param $class, additional class - use js_hide or js_show for default
| @param $attributes, array of other attributes to add to the tag
| @note also uses js to insert arrow after the h3, h4, or .title trigger
| @return chainable object
*/
	protected function toggler($value = '', $style = '', $id = 1)
	{
		if ($value)
		{
			$value .= "<a href='' style='position:absolute; left:-5px; top:10px'>X</a>";
		}

		return self::add("<div class='toggler inline-block' style='$style' data-fake-id='#toggle-$id'>$value<span class='toggle_icon'><span class='vert_icon'></span><span class='hor_icon'></span></span></div>");
	}

	protected function toggled($value = '', $style = '')
	{
		return self::div("toggle_wrap", $value, ['style' => $style]);
	}

/**
| -------------------------------------------------------------------------
| Caption: wrapper to add a div with class caption
| -------------------------------------------------------------------------
| @param $value, the contents to insert into the div
| @param $class, additional classes to add
| @param $attributes, array of other attributes to add to the tag
| @note meant to add a text caption to a title or form
| @return chainable object
*/
	protected function caption($value = '', $class = '', $attributes = [])
	{
		return self::tag('strong', $value, "caption $class", $attributes);
	}

/**
| -------------------------------------------------------------------------
| Div: creates a div container of the specified class
| -------------------------------------------------------------------------
| @param $class, class of the div container
| @param $value, the contents to insert into the div
| @param $attributes, array of other attributes to add to the tag
| @note params are reversed since div is simply a container
| @return chainable object
*/
	protected function div($class = '', $value = '', $attributes = [])
	{
		return self::tag('div', $value, $class, $attributes);
	}

/**
| -------------------------------------------------------------------------
| Ul: create an unordered (bulleted) list
| -------------------------------------------------------------------------
| @param $class, class of the ul container
| @param $value, the contents to insert into the div
| @param $attributes, array of other attributes to add to the tag
| @note params are reversed since ul is simply a container
| @return chainable object
*/
	protected function ul($class = '', $value = '', $attributes = [])
	{
		return self::tag('ul', $value, $class, $attributes);
	}


/**
| -------------------------------------------------------------------------
| Box/Info/Error: creates a div with class info only if value is not empty
| -------------------------------------------------------------------------
| @param $class, class of the ul container
| @param $value, the contents to insert into the div
| @param $attributes, array of other attributes to add to the tag
*/
	protected function box($value = '', $class = '', $title = '', $attributes = array())
	{
		if ( ! $value)
		{
			return $this;
		}

		return self::div("avia_message_box avia-size-normal $class", "", $attributes)

							->div('avia_message_box_title', $title)

							->div('avia_message_box_content', $value)

						->end();
	}

	protected function info($value = '', $class = '', $attributes = array())
	{
		return self::box($value, "avia-color-blue $class", 'Info', $attributes);
	}

	protected function alert($value = '', $class = '', $attributes = array())
	{
		return self::box($value, "avia-color-red $class", 'Alert', $attributes);
	}

	protected function note($value = '', $class = '', $attributes = array())
	{
		return self::box($value, "avia-color-grey $class", 'Note', $attributes);
	}


/**
| -------------------------------------------------------------------------
| Add: inserts plain text or html to the object without adding tags
| -------------------------------------------------------------------------
| @param $value, the contents to insert into the html object's string
| @param insert plain text or custom html without breaking the chain
| @return chainable object
*/
	protected function add($value = '')
	{
		$this->string .= $value;

		return $this;
	}

/**
| -------------------------------------------------------------------------
| End: closes corresponding tag(s) before the trigger does
| -------------------------------------------------------------------------
| @param $num, number of tags to close
| @return chainable object
*/
	protected function end($num = 1)
	{
		foreach(array_splice($this->tags, 0, $num) as $tag)
		{
			$this->string .= $tag;
		}

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Label: creates a label tag with nested span tag, allows styles to be set
| -------------------------------------------------------------------------
| @param $label, contents of the label
| @param $span, contents of the span (title)
| @param $lstyle, in-line style for the label
| @param $sstyle, in-line style for the span
| @return chainable object
*/
	protected function label($label, $span = '&nbsp;', $lstyle = '', $sstyle = '')
	{
		  if (is_array($label)) log::error($label);

		  $this->string .= "\n<label style='$lstyle'>\n\t<span style='$sstyle'>$span</span>\n\t$label\n</label>";

		  return $this;
	}

/**
| -------------------------------------------------------------------------
| Popup: link uses js for popup but degrades gracefully.
| -------------------------------------------------------------------------
| @param $uri, uri/url to open in a popup window
| @param $title, text to display. if blank displays $uri
| @param $class, class of the ul container
| @param $attributes, array of other attributes to add to the tag
| @note adds js confirm if $attributes['msg'] set
| @return chainable object
*/
	protected function popup($uri = '', $title = '', $class = '', $attributes = [])
	{
		$this->load->helper('url');

		if (isset($attributes['msg']))
		{
			$attributes['onClick'] = 'javascript: return confirm(\' '.$attributes['msg'].' \' );' ;
		}

		$this->string .= anchor_popup($uri, $title, $attributes + ['target' => '_blank'] + compact('class'));

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Link: creates a text hyperlink
| -------------------------------------------------------------------------
| @param $uri, uri/url to load
| @param $title, text to display. if blank displays $uri
| @param $class, class(es) of the hyperlink
| @param $attributes, array of other attributes to add to the tag
| @return chainable object
*/
	protected function link($uri = '', $title = '', $class = '', $attributes = [])
	{
		$this->load->helper('url');

		$this->string .= anchor($uri, $title, $attributes + compact('class'));

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Button: creates a button hyperlink
| -------------------------------------------------------------------------
| @param $uri, uri/url to load
| @param $title, text to display inside button.
| @param $class, additional class(es) to add to button
| @param $attributes, array of other attributes to add to the tag
| @return chainable object
*/
	protected function button($uri, $title = '', $class = '', $attributes = [])
	{
		return self::link($uri, $title, $class = "avia-button $class", $attributes);
	}

/**
| -------------------------------------------------------------------------
| Mail: creates a safe mail to hyperlink
| -------------------------------------------------------------------------
| @param $email, email to open in user's native email client
| @param $title, text to display if blank displays email
| @param $class, additional class(es) to add to the link
| @param $attributes, array of other attributes to add to the tag
| @return chainable object
*/
	protected function mail($email, $title = '', $class = '', $attributes = [])
	{
		$this->load->helper('url');

		$this->string .= mailto($email, $title, $attributes + compact('class'));

		return $this;
	}

/**
| -------------------------------------------------------------------------
| Image: insert an image contained in the folder defined in file::path('pic')
| -------------------------------------------------------------------------
| @param $name, name or path relative to the pic path of the image to display
| @param $style, inline css style to add to the picture
| @param $attributes, array of other attributes to add to the tag
| @note if name has no subfolder then assume its in folder named after controller
| @return chainable object
*/
	protected function image($name, $style = '', $attributes = [])
	{
		$this->string .= img(['style' => $style, 'src'=> $name[0] == '/' ? $name : file::path('images', $name)] + $attributes);

		return $this;
	}


} //END OF CLASS AND FILE