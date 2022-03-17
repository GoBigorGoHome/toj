<?php
	if (!Auth::check()) {
		becomeMsgPage(UOJLocale::get('need login'));
	}

	$username = $_GET['username'];
?>
<?php if (validateUsername($username) && ($user = queryUser($username))): ?>
	<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>
	<?php
		$esc_email = HTML::escape($user['email']);
		$esc_qq = HTML::escape($user['qq'] != 0 ? $user['qq'] : 'Unfilled');
		$esc_sex = HTML::escape($user['sex']);
		$col_sex="color:blue";
		if ($esc_sex == "M") {
			$esc_sex="♂";
			$col_sex="color:blue";
		} elseif ($esc_sex == "F") {
			$esc_sex="♀";
			$col_sex="color:red";
		} else {
			$esc_sex="";
			$col_sex="color:black";
		}
		$esc_motto = HTML::escape($user['motto']);
	?>
	<div class="card border-info">
		<h5 class="card-header bg-info"><?= UOJLocale::get('user profile') ?></h5>
		<div class="card-body">
			<div class="row mb-4">
				<div class="col-md-4 order-md-9">
					<img class="media-object img-thumbnail d-block mx-auto" alt="<?= $user['username'] ?> Avatar" src="<?= HTML::avatar_addr($user, 256) ?>" />
				</div>
				<div class="col-md-8 order-md-1">
					<h2><span class="uoj-honor" data-realname="<?= $user['realname'] ?>"><?= $user['username'] ?></span> <span><strong style="<?= $col_sex ?>"><?= $esc_sex ?></strong></span></h2>
					<div class="list-group">
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('email') ?></h4>
							<p class="list-group-item-text"><?= $esc_email ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('QQ') ?></h4>
							<p class="list-group-item-text"><?= $esc_qq ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('motto') ?></h4>
							<p class="list-group-item-text"><?= $esc_motto ?></p>
						</div>
						<?php if (isSuperUser($myUser)): ?>
						<div class="list-group-item">
							<h4 class="list-group-item-heading">register time</h4>
							<p class="list-group-item-text"><?= $user['register_time'] ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading">remote_addr</h4>
							<p class="list-group-item-text"><?= $user['remote_addr'] ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading">http_x_forwarded_for</h4>
							<p class="list-group-item-text"><?= $user['http_x_forwarded_for'] ?></p>
						</div>
						<?php endif ?>
					</div>
				</div>
			</div>
			<?php if (Auth::check()): ?>
			<?php if (Auth::id() != $user['username']): ?>
			<a type="button" class="btn btn-info btn-sm" href="/user/msg?enter=<?= $user['username'] ?>"><span class="glyphicon glyphicon-envelope"></span> <?= UOJLocale::get('send private message') ?></a>
			<?php else: ?>
			<a type="button" class="btn btn-info btn-sm" href="/user/modify-profile"><span class="glyphicon glyphicon-pencil"></span> <?= UOJLocale::get('modify my profile') ?></a>
			<?php endif ?>
			<?php endif ?>
			
			<a type="button" class="btn btn-success btn-sm" href="<?= HTML::blog_url($user['username'], '/') ?>"><span class="glyphicon glyphicon-arrow-right"></span> <?= UOJLocale::get('visit his blog', $username) ?></a>
			
			<div class="top-buffer-lg"></div>
			<div class="list-group">
				<div class="list-group-item">
					<?php
						$ac_problems = DB::selectAll("select problem_id from best_ac_submissions where submitter = '{$user['username']}'");
					?>
					<h4 class="list-group-item-heading"><?= UOJLocale::get('accepted problems').'：'.UOJLocale::get('n problems in total', count($ac_problems))?> </h4>
					<p class="list-group-item-text">
					<?php
						foreach ($ac_problems as $problem) {
							echo '<a href="/problem/', $problem['problem_id'], '" style="display:inline-block; width:4em;">', $problem['problem_id'], '</a>';
						}
						if (empty($ac_problems)) {
							echo UOJLocale::get('none');
						}
					?>
					</p>
				</div>
			</div>
		</div>
	</div>
<?php else: ?>
	<?php echoUOJPageHeader('不存在该用户' . ' - 用户信息') ?>
	<div class="card border-danger">
		<div class="card-header bg-danger">用户信息</div>
		<div class="card-body">
		<h4>不存在该用户</h4>
		</div>
	</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
