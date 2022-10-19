<?php
	requireLib('bootstrap5');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	$config = [
		'page_len' => 50,
		'div_classes' => ['card', 'mb-3'],
		'table_classes' => ['table', 'uoj-table', 'mb-0', 'text-center'],
	];

	if (isset($_GET['type']) && $_GET['type'] == 'accepted') {
		$config['by_accepted'] = true;
		$title = UOJLocale::get('top solver');
	} else {
		become404Page();
	}
	?>

<?php echoUOJPageHeader($title) ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">
<h1 class="h2"><?= $title ?></h1>

<?php echoRanklist($config) ?>
</div>
<!-- end left col -->

<!-- right col -->
<aside class="col-lg-3 mt-3 mt-lg-0">
<?php uojIncludeView('sidebar', array()) ?>
</aside>
<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
