<!DOCTYPE html>
<html lang='en' class="html_stretched responsive  html_header_top html_logo_left html_main_nav_header html_menu_right html_custom html_header_sticky html_header_shrinking html_mobile_menu_phone html_disabled html_entry_id_30  avia_desktop  js_active  avia_transform  avia_transform3d  avia-webkit avia-webkit-35 avia-mac">
	<head>
		<title><?= $title ?></title>
		<link rel="icon"       type="image/gif" href="/images/favicon.ico">
		<link rel='stylesheet' type='text/css'  href='/css/grid.css?ver=2'>
		<link rel='stylesheet' type='text/css'  href='/css/base.css?ver=2'>
		<link rel='stylesheet' type='text/css'  href='/css/layout.css?ver=2'>
		<link rel='stylesheet' type='text/css'  href='/css/prettyPhoto.css?ver=2'>
		<link rel='stylesheet' type='text/css'  href='/css/shortcodes.css?ver=2'>
		<link rel='stylesheet' type='text/css'  href='/css/enfold.css?ver=1'>
		<link rel='stylesheet' type='text/css'  href='/css/custom.css?ver=2'>
		<link rel='stylesheet' type='text/css'  href='/css/sirum.css'>

		<script type='text/javascript' src='/js/jquery.js?ver=1.11.0'></script>
		<script type='text/javascript' src='/js/jquery-migrate.min.js?ver=1.2.1'></script>
		<script type='text/javascript' src='/js/autocomplete.js?ver=1.2.11'></script>
		<script type='text/javascript' src='/js/validate.min.js?ver=1.9.0'></script>
		<script type='text/javascript' src='/js/avia-compat.js?ver=2'></script>
		<script type='text/javascript' src='/js/avia.js?ver=2'></script>
		<script type='text/javascript' src='/js/shortcodes.js?ver=2'></script>
		<script type='text/javascript' src='/js/prettyPhoto.js?ver=3.1.5'></script>
		<!--[if lt IE 9]>
		<script type='text/javascript' src="/js/html5shiv.js"></script>
		<![endif]-->
		<script type='text/javascript'>
			//Label html so we can do specific css styles
			if (window.top != window.self)
			{
				document.getElementsByTagName('html')[0].setAttribute('id', 'lightbox');
			}

			//jQuery
			!function($)
			{
				$(document).ready(function()
				{
					var reEscape = new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g');

					function formatResult(url)
					{
						return function(suggested, current)
						{
							var pattern = '(' + current.replace(reEscape, '\\$1') + ')';
							return '<div class="main_color" style="font-size:12px; background:inherit">'+suggested.value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>')+'<input type="button" value="Add" class="avia-button avia-color-theme-color floatright tr-hover" /></div>';
						}
					}

					var options =
					{
						minChars:4,
						noCache:true, //Caching caused issues with Allison
						formatResult: formatResult('/inventory/add'),
						serviceUrl:'/ajax/autocomplete',
						onSelect:function(suggested, e)
						{
							var button = 'Search'
							//TODO Fragile! this only works if upcs are listed last and all nine-digits
							this.form.upc.value =  suggested.value.slice(-9);

							//Make correct button appear in $_POST: tab == 9 & enter == 13
							if (e.which == 9 || e.target.value == 'Add')
							{
								button = 'Add'
								$("<input>").attr("type", "hidden").attr("name", "item_id").val(suggested.data).appendTo(this.form)
							}

							$("<input>").attr("type", "hidden").attr("name", "button").val(button).appendTo(this.form)

							this.form.submit();
						},
						autoSelectFirst:false,
						showNoSuggestionNotice:true
						//TODO prefetch all items?
						//lookup: [{value:'January', data:99999}]
					}

					$('#addinventory').autocomplete(options)

					options.formatResult = formatResult('/donations/add')

					$('#adddonation').autocomplete(options)

					//Barcode scanner presses enter afterwards.  Disable this behavior, user must click a button
					$('#addinventory, #adddonation').on("keypress", function(e)
					{
						return e.which != 13;
					})

					options.formatResult = function(suggested, current)
					{
						var pattern = '(' + current.replace(reEscape, '\\$1') + ')';
						return '<div onclick="event.cancelBubble = true" class="main_color" style="font-size:12px; background:inherit">'+suggested.value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>')+'<input type="checkbox" name="upc[]" value="'+suggested.data+'" checked class="avia-button avia-color-theme-color floatright" /></div>';
					}

					$('#addrequest').autocomplete(options)

					/* --------------------------  Toggle Checkboxes  -------------------------- */

					$("th input:checkbox").click(function ()
					{
						 if($(this).is(':checked'))
						 {
							$("td input:checkbox").prop('checked', true);
						 }
						 else
						 {
							 $("td input:checkbox").prop('checked', false);
						 }
					});

					$("td input:checkbox").click(function ()
					{
						 if( ! $(this).is(':checked'))
						 {
							$("th input:checkbox").prop('checked', false);
						 }
					});

					$('.check_row').keydown(function()
					{
						if ($(this).val().length > 0)
						{
							$(this).closest('tr').find(':checkbox').prop('checked', true);
						}
					});

					/* --------------------------  Toggle Checkboxes  -------------------------- */


					$('input[placeholder="mm/yy"]').bind('keyup',function(e)
					{
						var val = $(this).val()

						//On delete, remove the slash automatically
						if (val.length == 2 && e.which == 8)
						{
							$(this).val(val.slice(0, -1));
						}
						//Add a slash after 2nd digit unless they used m/yy format
						else if (val.length == 2 && val.slice(-1) != '/')
						{
							$(this).val(val+'/');
						}
						//Prevent common mistake of manually adding a 2nd "/"
						else if (val.length == 4 && e.which == 191)
						{
							$(this).val(val.slice(0, -1));
						}
					});
				});
			}(jQuery)
		</script>
	</head>
<body id="top">