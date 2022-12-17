<header id='header' class=' header_color light_bg_color mobile_slide_out av_header_top av_logo_left av_main_nav_header av_menu_right av_custom av_header_sticky av_header_shrinking av_header_stretch_disabled av_mobile_menu_phone'  role="banner" itemscope="itemscope" itemtype="https://schema.org/WPHeader" >

<a id="advanced_menu_toggle" href="#" aria-hidden='true' data-av_icon='' data-av_iconfont='entypo-fontello'></a><a id="advanced_menu_hide" href="#" 	aria-hidden='true' data-av_icon='?' data-av_iconfont='entypo-fontello'></a>

<div  id='header_main' class='container_wrap container_wrap_logo'>

<div class='container'>

<strong class='logo'><img height='100' width='300' src='/images/SIRUM_Logo2.png' alt='SIRUM' /></strong>

<nav class='main_menu' data-selectname='Select a page'  role="navigation" itemscope="itemscope" itemtype="https://schema.org/SiteNavigationElement" >
	<div class="avia-menu av-main-nav-wrap">
		<ul id="avia-menu" class="menu av-main-nav">
			<li class="<?= 'Inventory' == $nav ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-top-level ">
				<a href="/inventory"><span class="avia-bullet"></span><span class="avia-menu-text">Inventory</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
			</li>

			<li class="<?= 'Formulary' == $nav ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-top-level ">
				<a href="/formulary"><span class="avia-bullet"></span><span class="avia-menu-text">Formulary</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
			</li>

			<li class="<?= 'Donations' == $nav ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-top-level ">
				<a href="/donations"><span class="avia-bullet"></span><span class="avia-menu-text">Donations</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
			</li>

			<li class="<?= 'Records' == $nav ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children menu-item-top-level ">
				<a href="/record/donated"><span class="avia-bullet"></span><span class="avia-menu-text">Records</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
				<ul class="sub-menu">
					<li class="menu-item menu-item-type-custom menu-item-object-custom">
						<a href="/record/donated"><span class="avia-bullet"></span><span class="avia-menu-text">Donated Record</span></a>
					</li>
					<li class="menu-item menu-item-type-custom menu-item-object-custom">
						<a href="/record/received"><span class="avia-bullet"></span><span class="avia-menu-text">Received Record</span></a>
					</li>
					<li class="menu-item menu-item-type-custom menu-item-object-custom">
						<a href="/record/verified"><span class="avia-bullet"></span><span class="avia-menu-text">Verified Record</span></a>
					</li>
					<li class="menu-item menu-item-type-custom menu-item-object-custom">
						<a href="/record/destroyed"><span class="avia-bullet"></span><span class="avia-menu-text">Destruction Record</span></a>
					</li>
				</ul>
			</li>

			<li id="menu-item-89" class="<?= 'Account' == $nav ? 'current_page_parent' : '' ?> menu-item menu-item-type-post_type menu-item-object-page menu-item-top-level ">
				<a href="/account"><span class="avia-bullet"></span><span class="avia-menu-text">Account</span>
				<span class="avia-menu-fx"><span class="avia-arrow-wrap"><span class="avia-arrow"></span></span></span></a>
				<ul class="sub-menu">
					<li class="menu-item menu-item-type-custom menu-item-object-custom">
						<a href="/account"><span class="avia-bullet"></span><span class="avia-menu-text">Organzation Profile</span></a>
					</li>
					<li class="menu-item menu-item-type-custom menu-item-object-custom">
						<a href="/account/users"><span class="avia-bullet"></span><span class="avia-menu-text">Authorized Users</span></a>
					</li>
					<li class="menu-item menu-item-type-custom menu-item-object-custom">
						<a href="/account/donors"><span class="avia-bullet"></span><span class="avia-menu-text">Approved Donors</span></a>
					</li>
				</ul>
			</li>

			<li id="menu-item-90" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-top-level ">
				<a href="<?= data::get('admin_id') ? '/admin/swap/'.data::get('admin_id').'?to=admin/accounts' : '/login/sign_out' ?>"><span class="avia-bullet"></span><span class="avia-menu-text"><?= data::get('admin_id') ? 'Admin' : 'Sign Out'?></span>
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
