<?php
if (!isset($ShowPageFooter)) {
	$ShowPageFooter = true;
}
?>
</div>
<script>
	$(document).ready(function() {
		[...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
	});
</script>
<?php if ($ShowPageFooter) : ?>
	<?php if (UOJNotice::shouldConstantlyCheckNotice()) : ?>
		<script type="text/javascript">
			<?php UOJNotice::printJS(); ?>
		</script>
	<?php endif ?>
	<?php if (isset($REQUIRE_LIB['bootstrap5'])) : ?>
		<footer class="bg-white text-muted pt-3 pb-4 mt-4" style="font-size: 0.9em">
			<div class="container d-lg-flex justify-content-lg-between">
				<p>
					&copy; <?= date('Y') ?>
					<a class="text-decoration-none" href="<?= HTML::url('/') ?>">S2OJ</a>
					(build: <a class="text-decoration-none" href="https://github.com/renbaoshuo/S2OJ<?= UOJConfig::$data['profile']['s2oj-version'] == "dev" ? '' : '/tree/' . UOJConfig::$data['profile']['s2oj-version'] ?>"><?= UOJConfig::$data['profile']['s2oj-version'] ?></a>)
					<?= UOJLocale::get('server time') ?>: <?= UOJTime::$time_now_str ?>
					<?php if (UOJConfig::$data['profile']['ICP-license'] != '') : ?>
						| <a class="text-muted text-decoration-none" target="_blank" href="https://beian.miit.gov.cn">
							<?= UOJConfig::$data['profile']['ICP-license'] ?>
						</a>
					<?php endif ?>
				</p>
				<p>
					Based on
					<a class="text-decoration-none" href="https://uoj.ac" target="_blank">UOJ</a>,
					modified by
					<a class="text-decoration-none" href="https://baoshuo.ren" target="_blank">Baoshuo</a>
					for
					<a class="text-decoration-none" href="http://www.sjzez.com">SJZEZ</a>.
				</p>
			</div>
		</footer>
	<?php else : ?>
		<div class="uoj-footer">
			<div class="btn-group dropright mb-3">
				<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
					<span class="glyphicon glyphicon-globe"></span> <?= UOJLocale::get('_common_name') ?>
				</button>
				<div class="dropdown-menu">
					<a class="dropdown-item" href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'zh-cn'))) ?>">中文</a>
					<a class="dropdown-item" href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'en'))) ?>">English</a>
				</div>
			</div>

			<p><?= UOJLocale::get('server time') ?>: <?= UOJTime::$time_now_str ?></p>
			<p>
				<a href="https://github.com/renbaoshuo/S2OJ<?= UOJConfig::$data['profile']['s2oj-version'] == "dev" ? '' : '/tree/' . UOJConfig::$data['profile']['s2oj-version'] ?>">S2OJ (build: <?= UOJConfig::$data['profile']['s2oj-version'] ?>)</a>
				<?php if (UOJConfig::$data['profile']['ICP-license'] != '') : ?>
					| <a target="_blank" href="https://beian.miit.gov.cn" style="text-decoration:none;"><?= UOJConfig::$data['profile']['ICP-license'] ?></a>
				<?php endif ?>
			</p>
		</div>
	<?php endif ?>
<?php endif ?>
</div>
<!-- /container -->
</body>

</html>
