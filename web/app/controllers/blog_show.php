<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id']))) {
		become404Page();
	}
	
	redirectTo(HTML::blog_url($blog['poster'], '/post/'.$_GET['id']));
?>
