<?php
	requireLib('bootstrap5');
	requireLib('md5');
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!validateUsername($_GET['username']) || !($user = queryUser($_GET['username']))) {
		become404Page();
	}

	if (!isSuperUser($myUser) && $myUser['username'] != $user['username']) {
		become403Page();
	}

	if (isset($_GET['tab'])) {
		$cur_tab = $_GET['tab'];
	} else {
		$cur_tab = 'profile';
	}
	
	$tabs_info = [
		'profile' => [
			'name' => '<i class="bi bi-person-fill"></i> 个人资料',
			'url' => "/user/{$user['username']}/edit/profile",
		],
		'password' => [
			'name' => '<i class="bi bi-key-fill"></i> 修改密码',
			'url' => "/user/{$user['username']}/edit/password",
		],
	];
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}

	if ($cur_tab == 'profile') {
		$update_profile_form = new UOJForm('update_profile');
		$username = UOJLocale::get('username');
		$avatar = UOJLocale::get('avatar');
		$change_avatar_help = UOJLocale::get('change avatar help');
		$update_profile_form->appendHTML(<<<EOD
	<div class="mb-3">
		<label for="input-username" class="form-label">$username</label>
		<input type="text" class="form-control" id="input-username" aria-describedby="help-username" value="{$user['username']}" disabled>
		<div id="help-username" class="form-text">用户名不能被修改。</div>
	</div>
	<div class="mb-3">
		<div>$avatar</div>
		<div class="mt-1 small text-muted">$change_avatar_help</div>
	</div>
EOD);
		$update_profile_form->addVInput('email', 'email', UOJLocale::get('email'), $user['email'],
			function($email, &$vdata) {
				if (!validateEmail($email)) {
					return 'Email 格式不合法。';
				}

				$vdata['email'] = $email;

				return '';
			}, null);
		$update_profile_form->addVInput('qq', 'text', UOJLocale::get('QQ'), $user['qq'] == 0 ? '' : $user['qq'],
			function($qq, &$vdata) {
				if ($qq && !validateQQ($qq)) {
					return 'QQ 格式不合法。';
				}

				$vdata['qq'] = $qq;

				return '';
			}, null);
		if (isSuperUser($myUser)) {
			$update_profile_form->addVInput('school', 'text', UOJLocale::get('school'), $user['school'],
				function($school, &$vdata) {
					$vdata['school'] = $school;

					return '';
				}, null);
		} else {
			$school = UOJLocale::get('school');
			$update_profile_form->appendHTML(<<<EOD
	<div class="mb-3">
		<label for="input-school" class="form-label">$school</label>
		<input type="text" class="form-control" id="input-school" aria-describedby="help-school" value="{$user['school']}" disabled>
		<div id="help-school" class="form-text">只有管理员才能修改用户所属学校。</div>
	</div>
EOD);
		}
		$update_profile_form->addVSelect('sex', [
				'U' => UOJLocale::get('refuse to answer'),
				'M' => UOJLocale::get('male'),
				'F' => UOJLocale::get('female'),
			], UOJLocale::get('sex'), $user['sex']);
		$update_profile_form->addVInput('motto', 'text', UOJLocale::get('motto'), $user['motto'],
			function($motto, &$vdata) {
				if (!validateMotto($motto)) {
					return '格言格式不合法';
				}

				$vdata['motto'] = $motto;

				return '';
			}, null);
		$update_profile_form->addVInput('codeforces_handle', 'text', UOJLocale::get('codeforces handle'), $user['codeforces_handle'],
			function($codeforces_handle, &$vdata) {
				if ($codeforces_handle && !validateUsername($codeforces_handle)) {
					return 'Codeforces 用户名格式不合法。';
				}

				$vdata['codeforces_handle'] = $codeforces_handle;

				return '';
			}, null);
		$update_profile_form->handle = function(&$vdata) use ($user, $myUser) {
			$esc_email = DB::escape($vdata['email']);
			$esc_qq = DB::escape($vdata['qq']);
			$esc_sex = DB::escape($vdata['sex']);
			$esc_motto = DB::escape($vdata['motto']);
			$esc_codeforces_handle = DB::escape($vdata['codeforces_handle']);

			if (isSuperUser($myUser)) {
				$esc_school = DB::escape($vdata['school']);

				DB::update("UPDATE user_info SET school = '$esc_school' WHERE username = '{$user['username']}'");
			}

			DB::update("UPDATE user_info SET email = '$esc_email', qq = '$esc_qq', sex = '$esc_sex', motto = '$esc_motto', codeforces_handle = '$esc_codeforces_handle' WHERE username = '{$user['username']}'");
		};
		$update_profile_form->submit_button_config['margin_class'] = 'mt-3';
		$update_profile_form->submit_button_config['text'] = '更新';
		$update_profile_form->runAtServer();
	} elseif ($cur_tab == 'password') {
		if (isset($_POST['submit-change_password']) && $_POST['submit-change_password'] == 'change_password') {
			header('Content-Type: application/json');

			$old_password = $_POST['current_password'];
			$new_password = $_POST['new_password'];

			if (!validatePassword($old_password) || !checkPassword($user, $old_password)) {
				die(json_encode(['status' => 'error', 'message' => '旧密码错误']));
			}

			if (!validatePassword($new_password)) {
				die(json_encode(['status' => 'error', 'message' => '新密码不合法']));
			}

			if ($old_password == $new_password) {
				die(json_encode(['status' => 'error', 'message' => '新密码不能与旧密码相同']));
			}

			$password = getPasswordToStore($new_password, $user['username']);
			DB::update("UPDATE `user_info` SET `password` = '$password' where `username` = '{$user['username']}'");
			die(json_encode(['status' => 'success', 'message' => '密码修改成功']));
		}
	}

	$pageTitle = $user['username'] == $myUser['username']
		? UOJLocale::get('modify my profile')
		: UOJLocale::get('modify his profile', $user['username'])
	?>

<?php echoUOJPageHeader($pageTitle) ?>

<h1 class="h2">
	<?= $pageTitle ?>
</h1>

<div class="row mt-4">
<!-- left col -->
<div class="col-md-3">

<div class="list-group">
	<?php foreach ($tabs_info as $id => $tab): ?>
	<a
		role="button"
		class="list-group-item list-group-item-action <?= $cur_tab == $id ? 'active' : '' ?>"
		href="<?= $tab['url'] ?>">
		<?= $tab['name'] ?>
	</a>
	<?php endforeach ?>
</div>

<a
	class="btn btn-light d-block mt-2 w-100 text-start text-primary"
	style="--bs-btn-hover-bg: #d3d4d570; --bs-btn-hover-border-color: transparent;"
	href="<?= HTML::url("/user/{$user['username']}") ?>">
	<i class="bi bi-arrow-left"></i> 返回
</a>

<?php if (isSuperUser($myUser) && $user['username'] != $myUser['username']): ?>
<div class="alert alert-warning mt-3 small" role="alert">
	您正在使用管理特权查看并编辑其它用户的资料。
</div>
<?php endif ?>

</div>
<!-- end left col -->

<!-- right col -->
<div class="col-md-9">
<?php if ($cur_tab == 'profile'): ?>
	<div class="card">
		<div class="card-body">
			<?php $update_profile_form->printHTML() ?>
		</div>
	</div>
<?php elseif ($cur_tab == 'password'): ?>
	<div class="card">
		<div class="card-body">
			<div id="result-alert" class="alert" role="alert" style="display: none"></div>
			<form method="post" id="form-change_password">
				<div class="mb-3">
					<label for="input-current_password" class="form-label">
						<?= UOJLocale::get('current password') ?>
					</label>
					<input type="password" class="form-control" id="input-current_password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20">
					<div id="help-current_password" class="invalid-feedback"></div>
				</div>
				<div class="mb-3">
					<label for="input-new_password" class="form-label">
						<?= UOJLocale::get('new password') ?>
					</label>
					<input type="password" class="form-control" id="input-new_password" placeholder="<?= UOJLocale::get('enter your new password') ?>" maxlength="20">
					<div id="help-new_password" class="invalid-feedback"></div>
				</div>
				<div class="mb-3">
					<label for="input-confirm_password" class="form-label">
						<?= UOJLocale::get('confirm new password') ?>
					</label>
					<input type="password" class="form-control" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your new password') ?>" maxlength="20">
					<div id="help-confirm_password" class="invalid-feedback"></div>
				</div>
				<?php if (isSuperUser($myUser) && $user['username'] != $myUser['username']): ?>
				<div class="alert alert-warning mb-0" role="alert">
					如需修改其他用户的密码，请前往 <a href="/super-manage/users" class="alert-link">系统管理</a> 页面操作。
				</div>
				<?php endif ?>
				<div class="text-center">
					<button type="submit" id="button-submit-change_password" name="submit-change_password" value="change_password" class="mt-3 btn btn-secondary">更新</button>
				</div>
			</form>
		</div>
	</div>
	<script>
		$('#form-change_password').submit(function() {
			var ok = true;

			ok &= getFormErrorAndShowHelp('current_password', validatePassword);
			ok &= getFormErrorAndShowHelp('new_password', validateSettingPassword);

			if (ok) {
				$.ajax({
					method: 'POST',
					data: {
						'submit-change_password': 'change_password',
						'current_password': md5($('#input-current_password').val(), "<?= getPasswordClientSalt() ?>"),
						'new_password': md5($('#input-new_password').val(), "<?= getPasswordClientSalt() ?>"),
					},
					success: function(res) {
						if (res.status === 'success') {
							$('#result-alert')
								.html('密码修改成功！')
								.addClass('alert-success')
								.removeClass('alert-danger')
								.show();
						} else {
							$('#result-alert')
								.html('密码修改失败。' + (res.message || ''))
								.removeClass('alert-success')
								.addClass('alert-danger')
								.show();
						}
					},
					error: function() {
						$('#result-alert')
							.html('密码修改失败：请求失败。')
							.removeClass('alert-success')
							.addClass('alert-danger')
							.show();
					}
				});
			}

			return false;
		});
	</script>
<?php endif ?>
<!-- end right col -->
</div>
</div>

<?php echoUOJPageFooter() ?>
