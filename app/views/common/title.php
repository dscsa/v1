<div id="main" data-scroll-offset="65">
	<div class="stretch_full container_wrap alternate_color light_bg_color title_container">
	<div class="container">
	<h1 class="main-title entry-title">
	<?= "$title" ?>
	</h1>
	<div class="breadcrumb breadcrumbs avia-breadcrumbs">
	<div class="breadcrumb-trail" xmlns:v="http://rdf.data-vocabulary.org/#">
	<span class="trail-before">
	<span class="breadcrumb-title">Welcome <?= data::get('org_name') ?>&nbsp;&nbsp;:</span>
	</span>
	<span typeof="v:Breadcrumb">
	<a rel="v:url" property="v:title" href="/<?= strtolower($nav) ?>" title="SIRUM" class="trail-begin"><?= $nav ?></a>
	</span>
	<span class="sep">/</span>
	<span typeof="v:Breadcrumb">
	<span class="trail-end"><?= $title ?></span>
	</span>
	</div>
	</div>
	</div>
	</div>
<div class = 'container'>