<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class form extends MY_Helper
{
	// no nested forms. tricky since html helper can put the form close tag into
	// a queue so we only want to accept no tags once the current tag has passed
	static $pipeline = false;

	/**
| -------------------------------------------------------------------------
| Trigger: methods can add to $string which will trigger when displayed
| -------------------------------------------------------------------------
| Rather than ending a method chain with a trigger, we can have each method
| append their result to the class variable $string before returning $this.
| We then use the __toString magic method to display the string in the view
| @note subclass must define protected $string variable within it
*/
	function __toString()
	{
		if (text::has($this->string, ['<form','</form']))
		{
			self::$pipeline = false;
		}

		return $this->string;
	}

/**
| ---------------------------------------------------------
| Submit: input with type, name = 'button' and class = 'avia-button'
| ---------------------------------------------------------
| @param $value, text to display on the button
| @param $class, classes in addition to 'button' to be used
| @param $attributes, array w/ extra or overriding attributes
| @note 'msg' key in attribute array adds js confirm box
| @return chainable form object
*/
	protected function submit($value, $class = 'avia-color-grey avia-size-medium', $attributes = [])
	{
		if (isset($data['msg']))
		{
			$data['onClick'] = 'javascript: return confirm(\' '.$data['msg'].' \' );' ;

			unset($data['msg']);
		}

		$this->string .= form_submit($attributes + ['name' => 'button', 'value' => $value, 'class' => "avia-button $class"]);

		return $this;
	}

/**
| ---------------------------------------------------------
| Update: submit button and either a cancel or delete button
| ---------------------------------------------------------
| @param $url ? cancel button with link : delete submit button
| @return chainable form object
*/
	protected function update($url, $class = 'main_color')
	{
		self::submit('Update', $class, ['style' => 'margin:0px 2px']);

		if($url)
		{
			$this->string .= html::button($url, 'Cancel', "avia-color-theme-color-subtle $class", ['style' => 'margin:0px 2px']);
		}
		else
		{
			self::submit('Delete', $class);
		}

		return $this;
	}

/**
| ---------------------------------------------------------
| Open: form open multipart with name form & target _parent
| ---------------------------------------------------------
| @param $action, url to submit to, defaults to current url
| @param $attributes, array w/ extra or overriding attributes
| @param $hidden, array with hidden variables to include
| @return chainable form object
*/
	protected function open($action = '', $attributes = [], $hidden = [])
	{
		if ( ! self::$pipeline)
		{
			$action = ($action ?: $this->uri->uri_string()).$this->router->get_string();

			$this->string .= form_open_multipart($action, $attributes + ['name' =>'', 'target' => '_parent'], $hidden);

			self::$pipeline = true;
		}

		return $this;
	}

/**
| ---------------------------------------------------------
| Upload: choose file form element with error field
| ---------------------------------------------------------
| @param $name, $_POST array's key pointing to files contents
| @param $class, css classes to be added
| @param $attributes, array w/ extra or overriding attributes
| @note unlike form_upload, function triggers validation rules
| @return chainable form object
*/
	protected function upload($name, $value = 'Upload', $class = '', $attributes = [])
	{
		$error = form_error($name);

		$this->string .= $error ? $error.br(1) : "";

		if (isset($attributes['hidden']))
		{
			$style = ifset($attributes['style']);

			$this->string .= form_upload($attributes + ['name' => $name]).
			"<button
				type='button'
				class='avia-button $class'
				style='$style'
				onclick=
				'
					var input = document.createElement(\"input\");
					input.setAttribute(\"name\", \"button\");
					input.setAttribute(\"type\", \"hidden\");
					input.setAttribute(\"value\", \"$value\");
					this.form.appendChild(input);
					this.form.".$name."[0].click();
				'
			>
				$value
			</button>";
		}
		else
		{
			$this->string .= form_upload($attributes + ['name' => $name, 'class' => $class, 'style' => "border:0px"]);
		}

		//This is needed to activate form_validation callback.
		$this->string .= form_hidden($name, $name);

		return $this;
	}

/**
| ---------------------------------------------------------
| Input: text input form element with error field
| ---------------------------------------------------------
| @param $name, $_POST array's key pointing to files contents
| @param $default, initial value, overriden by user submission
| @param $class, css classes to be added
| @param $attributes, array w/ extra or overriding attributes
| @note for a text area field use form::text instead
| @return chainable form object
*/
	protected function input($name, $default = '', $class = '', $attributes = [])
	{
		$value = data::all($name, $default);

		$this->string .= form_error($name).form_input($attributes + compact('name', 'class', 'value'));

		return $this;
	}

/**
| ---------------------------------------------------------
| Text: textarea form element with error field
| ---------------------------------------------------------
| @param $name, $_POST array's key pointing to files contents
| @param $default, initial value, overriden by user submission
| @param $class, css classes to be added
| @param $attributes, array w/ extra or overriding attributes
| @note use the attribute key 'rows' to set the height, default is 5
| @return chainable form object
*/
	protected function text($name, $default = '', $class = '', $attributes = [])
	{
		$this->string .= form_error($name).form_textarea($attributes + compact('name', 'class') + ['rows' => 5, 'value' => data::all($name, $default)]);

		return $this;
	}

/**
| ---------------------------------------------------------
| Password: password field with error
| ---------------------------------------------------------
| @param $name, $_POST array's key pointing to files contents
| @param $default, initial value, overriden by user submission
| @param $class, css classes to be added
| @param $attributes, array w/ extra or overriding attributes
| @return chainable form object
*/
	protected function password($name, $default = '', $class = '', $attributes = [])
	{
		$this->string .= form_error($name).form_password($attributes + compact('name', 'class') + ['value' => data::all($name, $default)]);

		return $this;
	}

/**
| ---------------------------------------------------------
| Text: turns values array into vertical checkboxes with labels
| ---------------------------------------------------------
| @param $name, $_POST array's key pointing to the box checked
| @param $checked, array of booleans for the default values
| @param $values, the checkbox's labels and values submitted
| @param $class, css classes to be added
| @param $attributes, array w/ extra or overriding attributes
| @return chainable form object
*/
	protected function checkbox($name, $checked = [], $values = [], $class = '', $attributes = [])
	{
		$checked = (array) $checked;

		$this->string .= form_error($name);

		foreach ( (array) $values as $value)
		{
			$this->string .= form_checkbox($attributes + compact('name', 'value', 'class') + ['checked' => data::all($name,  array_shift($checked))])." $value<br>";
		}

		return $this;
	}

/**
| ---------------------------------------------------------
| Radio: turns options array into vertical radio with labels
| ---------------------------------------------------------
| @param $name, $_POST array's key pointing to the box checked
| @param $default, value to select initially, defaults to first option
| @param $options, the checkbox's labels and values submitted
| @param $class, css classes to be added
| @param $attributes, array w/ extra or overriding attributes
| @param $note, if options empty, searches for defaults::$name()
| @return chainable form object
*/
	protected function radio($name, $default = '', $options = [], $class = 'inline-block', $attributes = [])
	{
		$options = $options ?: defaults::$name();

		$default = data::all($name, $default ?: reset(array_keys($options)));

		$this->string .= form_error($name);

		foreach ($options as $value => $label)
		{
			$this->string .= form_radio($attributes + compact('name', 'value', 'class') + ['checked' => $default == $value]).nbs(1)."$label<br>";
		}

		return $this;
	}

/**
| ---------------------------------------------------------
| Dropdown: display options in a dropdown field with error
| ---------------------------------------------------------
| @param $name, $_POST array's key pointing to the box checked
| @param $default, value to select initially, defaults to first option
| @param $options, the array of options for the user to select
| @param $class, css classes to be added
| @param $attributes, array w/ extra or overriding attributes
| @return chainable form object
*/
	protected function dropdown($name, $default = '',  $options, $class = '',  $attributes = [])
	{
		$options = $options ?: defaults::$name();

		$this->string .= form_error($name).form_dropdown($name, (array) $options, data::all($name, $default), _parse_form_attributes($attributes, compact('class')));

		return $this;
	}

/**
| ---------------------------------------------------------
| Close: add jquey validate rules from CI's validation library
| ---------------------------------------------------------
| @param $element, html element that the error is wrapped in
| @note needs to be used with an ajax controller with method remote
| @note based on http://www.arnas.net/blog/2011/03/codeigniter-javascript-validation/
| @return chainable form object
*/
	protected function close($element = 'strong')
	{
		if ( ! self::$pipeline)
		{
			$this->load->library('form_validation');

			$uri = $this->router->base();

			if (empty($this->form_validation->_config_rules[$uri]))
			{
				$this->string .= '</form>';
			}

			//type casting functions to arrays allows us to remove the extra double quotes more easily
			$options = array
			(
				//'errorClass'	=> 'required',
				'errorPlacement'=> (array) 'function(error, element) {error.insertBefore(element);}',
				'errorElement' 	=> $element,
				'onkeyup'	 	=> false,
				'ignore'		=> ':hidden',
				'onsubmit'		=> false,
				//'debug'			=> true
			);

			$fields = ifset($this->form_validation->_config_rules[$uri], array());

			foreach ($fields as $field)
			{
				$rules = explode('|', $field['rules']);

				// Will add built-in required rule
				$options['rules'][$field['field']] = array
				(
					'required'	=> in_array('required', $rules),
					'remote'	=> array('url' => '/ajax/remote', 'type' => 'post')
				);

				$options['rules'][$field['field']]['remote']['data'] = array
				(
					'form' 									=> $uri,
					$this->config->item('csrf_token_name') 	=> $this->security->csrf_hash
				);

				// "matches" and "is_unique" are special cases, since we need to pass an additional field
				foreach ($rules as $rule)
				{
					preg_match_all('/(.*?)\[(.*?)\]/', $rule, $matches);

					if (isset($matches[1][0]) && in_array($matches[1][0], ['matches', 'is_unique']))
					{
						$function = 'function() { return jQuery(\'[name='. addcslashes($matches[2][0], '.') .']\').val();}';

						$options['rules'][$field['field']]['remote']['data'][$matches[2][0]] = (array) $function;
					}
				}
			}

			$json = str_replace(['["', '"]'], '', json_encode($options));

			$this->string .= "\n<script type='text/javascript'>jQuery('form').validate($json)</script>\n</form>";

			self::$pipeline = true;
		}

		return $this;
	}
}

// ------------------------------------------------------------------------
