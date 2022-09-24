<?php
return [
	'_common_name' => 'English',
	'login' => 'Login',
	'register' => 'Register',
	'logout' => 'Logout',
	'need login' => 'You need to login first',
	'my profile' => 'My Profile',
	'private message' => 'Private Message',
	'system message' => 'System Message',
	'system manage' => 'System Manage',
	'contests' => 'Contests',
	'problems' => 'Problems',
	'problems lists' => 'Problems Lists',
	'groups' => 'Groups',
	'add new group' => 'Add new group',
	'users count' => 'Users',
	'submissions' => 'Submissions',
	'hacks' => 'Hack!',
	'blogs' => 'Blogs',
	'announcements' => 'Announcements',
	'all the announcements' => 'All the Announcements……',
	'solved' => 'Solved',
	'top solver' => 'Top solver',
	'n accepted in last year' => function($n) {
		return "Submitted $n AC code" . ($n > 1 ? "s" : "") . " in last year";
	},
	'help' => 'Help',
	'search' => 'Search',
	'news' => 'News',
	'assignments' => 'Assignments',
	'username' => 'Username',
	'password' => 'Password',
	'new password' => 'New password',
	'verification code' => 'Verification code',
	'email' => 'Email',
	'QQ' => 'QQ',
	'sex' => 'Sex',
	'motto' => 'Motto',
	'view all' => 'View all',
	'appraisal' => 'Appraisal',
	'submit' => 'Submit',
	'browse' => 'Browse',
	'score range' => 'Score range',
	'details' => 'Details',
	'hours' => function($h) {
		return "$h ".($h <= 1 ? 'hour' : 'hours');
	},
	'title' => 'Title',
	'content' => 'Content',
	'time' => 'Time',
	'none' => 'None',
	'user profile' => 'User profile',
	'send private message' => 'Send private message',
	'modify my profile' => 'Modify my profile',
	'visit his blog' => function($name) {
		return "Visit $name's blog";
	},
	'accepted problems' => 'Accepted problems',
	'n problems in total' => function($n) {
		return "$n ".($n <= 1 ? 'problem' : 'problems');
	},
	'please enter your password for authorization' => 'Please enter your password for authorization',
	'please enter your new profile' => 'Please enter your new profile',
	'leave it blank if you do not want to change the password' => 'Leave it blank if you do not want to change the password',
	'change avatar help' => 'Do you want to change your avatar? Please see <a href="/faq">Help</a>',
	'enter your username' => 'Enter your username',
	'enter your email' => 'Enter your email',
	'enter your password' => 'Enter your password',
	're-enter your password' => 'Re-enter your password',
	'enter your new password' => 'Enter your new password',
	're-enter your new password' => 'Re-enter your new password',
	'enter your QQ' => 'Enter your QQ',
	'enter verification code' => 'Enter verification code',
	'refuse to answer' => 'Refuse to answer',
	'male' => 'Male',
	'female' => 'Female',
	'countdowns' => 'Countdowns',
	'countdown title has begun' => function($title) {
		return "<b>$title</b> has begun.";
	},
	'x days until countdown title' => function($title, $days) {
		return "<b>$days</b> ".($days <= 1 ? 'day' : 'days')." until <b>$title</b>.";
	},
	'friend links' => 'Frequently Used Links',
	'server time' => 'Server Time',
	'opensource project' => 'OpenSource Project, modified by S2OJ'
];
