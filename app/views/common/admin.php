<header id='header' class=' header_color light_bg_color mobile_slide_out av_header_top av_logo_left av_main_nav_header av_menu_right av_custom av_header_sticky av_header_shrinking av_header_stretch_disabled av_mobile_menu_phone'  role="banner" itemscope="itemscope" itemtype="https://schema.org/WPHeader" >

<a id="advanced_menu_toggle" href="#" aria-hidden='true' data-av_icon='' data-av_iconfont='entypo-fontello'></a><a id="advanced_menu_hide" href="#" 	aria-hidden='true' data-av_icon='?' data-av_iconfont='entypo-fontello'></a>

<div  id='header_main' class='container_wrap container_wrap_logo'>

<div class='container'>

<strong class='logo'><img height='100' width='300' src='/images/SIRUM_Logo2.png' alt='SIRUM' /></strong>

<nav class='main_menu' data-selectname='Select a page'  role="navigation" itemscope="itemscope" itemtype="https://schema.org/SiteNavigationElement" >
	<div class="avia-menu av-main-nav-wrap">
		<ul id="avia-menu" class="menu av-main-nav">
			<li class="<?= 'Labels' == $title ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children menu-item-top-level ">
				<a href="/admin"><span class="avia-bullet"></span><span class="avia-menu-text">Labels</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
			</li>

			<li class="<?= 'Accounts' == $title ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children menu-item-top-level ">
				<a href="/admin/accounts"><span class="avia-bullet"></span><span class="avia-menu-text">Accounts</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
			</li>

			<li class="<?= 'Items' == $title ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children menu-item-top-level ">
				<a href="/admin/items"><span class="avia-bullet"></span><span class="avia-menu-text">Items</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
			</li>

			<li id="menu-item-89" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-top-level ">
				<a href="<?= data::get('admin_id') ? '/admin/swap/'.data::get('admin_id').'?to=admin/users' : '/login/sign_out' ?>"><span class="avia-bullet"></span><span class="avia-menu-text"><?= data::get('admin_id') ? 'Admin' : 'Sign Out'?></span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
			</li>
		</ul>
	</div>
</nav>


		        <!-- end container-->
		        </div>

		<!-- end container_wrap-->
		</div>

		<div class='header_bg'></div>

<!-- end header -->
</header>
