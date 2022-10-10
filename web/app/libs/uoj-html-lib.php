<?php

function uojHandleAtSign($str, $uri) {
	$referrers = array();
	$res = preg_replace_callback('/@(@|[a-zA-Z0-9_]{1,20})/', function($matches) use (&$referrers) {
		if ($matches[1] === '@') {
			return '@';
		} else {
			$user = queryUser($matches[1]);
			if ($user == null) {
				return $matches[0];
			} else {
				$referrers[$user['username']] = '';
				return '<span class="uoj-username">@'.$user['username'].'</span>';
			}
		}
	}, $str);
	
	$referrers_list = array();
	foreach ($referrers as $referrer => $val) {
		$referrers_list[] = $referrer;
	}
	
	return array($res, $referrers_list);
}

function uojFilePreview($file_name, $output_limit, $file_type = 'text') {
	switch ($file_type) {
		case 'text':
			return strOmit(file_get_contents($file_name, false, null, 0, $output_limit + 4), $output_limit);
		default:
			return strOmit(shell_exec('xxd -g 4 -l 5000 ' . escapeshellarg($file_name) . ' | head -c ' . ($output_limit + 4)), $output_limit);
	}
}

function uojIncludeView($name, $view_params = array()) {
	global $REQUIRE_LIB;
	extract($view_params);
	include $_SERVER['DOCUMENT_ROOT'].'/app/views/'.$name.'.php';
}

function redirectTo($url) {
	header('Location: '.$url);
	die();
}
function permanentlyRedirectTo($url) {
	header("HTTP/1.1 301 Moved Permanently"); 
	header('Location: '.$url);
	die();
}
function redirectToLogin() {
	if (UOJContext::isAjax()) {
		die('please <a href="'.HTML::url('/login').'">login</a>');
	} else {
		header('Location: '.HTML::url('/login'));
		die();
	}
}
function becomeMsgPage($msg, $title = '消息') {
	global $REQUIRE_LIB;

	if (UOJContext::isAjax()) {
		die($msg);
	} else {
		if (!isset($_COOKIE['bootstrap4'])) {
			$REQUIRE_LIB['bootstrap5'] = '';
		}

		echoUOJPageHeader($title);
		echo $msg;
		echoUOJPageFooter();
		die();
	}
}
function become404Page($message = '未找到页面。') {
	header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found", true, 404);
	becomeMsgPage('<div class="text-center"><div style="font-size:150px">404</div><p>' . $message . '</p></div>', '404');
}
function become403Page($message = '访问被拒绝，您可能需要适当的权限以访问此页面。') {
	header($_SERVER['SERVER_PROTOCOL'] . " 403 Forbidden", true, 403); 
	becomeMsgPage('<div class="text-center"><div style="font-size:150px">403</div><p>' . $message . '</p></div>', '403');
}

function getUserLink($username) {
	if (validateUsername($username) && ($user = queryUser($username)) && $user['usergroup'] != 'B') {
		$realname = $user['realname'];

		if ($realname == "") {
			return '<span class="uoj-username">'.$username.'</span>';
		} else {
			return '<span class="uoj-username" data-realname="'.$realname.'">'.$username.'</span>';
		}
	} else {
		$esc_username = HTML::escape($username);
		return '<span>'.$esc_username.'</span>';
	}
}

function getUserName($username, $realname = null) {
	if ($realname == null) {
		if (validateUsername($username) && ($user = queryUser($username))) {
			$realname = $user['realname'];
		}
	}

	if ($realname == "") {
		return "$username";
	} else {
		return "$username ($realname)";
	}
}


function getProblemLink($problem, $problem_title = '!title_only') {
	global $REQUIRE_LIB;

	if ($problem_title == '!title_only') {
		$problem_title = $problem['title'];
	} elseif ($problem_title == '!id_and_title') {
		$problem_title = "#${problem['id']}. ${problem['title']}";
	}
	$result = '<a ';
	if (isset($REQUIRE_LIB['bootstrap5'])) {
		$result .= ' class="text-decoration-none" ';
	}
	$result .= ' href="/problem/'.$problem['id'].'">'.$problem_title.'</a>';

	return $result;
}
function getContestProblemLink($problem, $contest_id, $problem_title = '!title_only') {
	global $REQUIRE_LIB;

	if ($problem_title == '!title_only') {
		$problem_title = $problem['title'];
	} elseif ($problem_title == '!id_and_title') {
		$problem_title = "#{$problem['id']}. {$problem['title']}";
	}
	$result = '<a ';
	if (isset($REQUIRE_LIB['bootstrap5'])) {
		$result .= ' class="text-decoration-none" ';
	}
	$result .= ' href="/contest/'.$contest_id.'/problem/'.$problem['id'].'">'.$problem_title.'</a>';

	return $result;
}
function getBlogLink($id) {
	global $REQUIRE_LIB;

	$result = '';
	if (validateUInt($id) && $blog = queryBlog($id)) {
		$result = '<a ';
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			$result .= ' class="text-decoration-none" ';
		}
		$result .= ' href="/blogs/'.$id.'">'.$blog['title'].'</a>';
	}

	return $result;
}
function getClickZanBlock($type, $id, $cnt, $val = null, $show_text = true) {
	if ($val == null) {
		$val = queryZanVal($id, $type, Auth::user());
	}
	return '<div class="uoj-click-zan-block" data-id="'.$id.'" data-type="'.$type.'" data-val="'.$val.'" data-cnt="'.$cnt.'" '.($show_text ? ' data-show-text="1" ' : '') . '></div>';
}


function getLongTablePageRawUri($page) {
	$path = strtok(UOJContext::requestURI(), '?');
	$query_string = strtok('?');
	parse_str($query_string, $param);
			
	$param['page'] = $page;
	if ($page == 1) {
		unset($param['page']);
	}
			
	if ($param) {
		return $path . '?' . http_build_query($param);
	} else {
		return $path;
	}
}
function getLongTablePageUri($page) {
	return HTML::escape(getLongTablePageRawUri($page));
}

function echoLongTable($col_names, $table_name, $cond, $tail, $header_row, $print_row, $config) {
	global $REQUIRE_LIB;

	$pag_config = $config;
	$pag_config['col_names'] = $col_names;
	$pag_config['table_name'] = $table_name;
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = $tail;
	$pag = new Paginator($pag_config);

	$div_classes = isset($config['div_classes']) ? $config['div_classes'] : array('table-responsive');
	$table_classes = isset($config['table_classes'])
		? $config['table_classes']
		: (isset($REQUIRE_LIB['bootstrap5'])
			? array('table', 'table-bordered', 'table-striped', 'text-center')
			: array('table', 'table-bordered', 'table-hover', 'table-striped', 'table-text-center'));
	
	if (isset($config['head_pagination']) && $config['head_pagination']) {
		echo $pag->pagination();
	}

	echo '<div class="', join($div_classes, ' '), '">';
	echo '<table class="', join($table_classes, ' '), '">';
	echo '<thead>';
	echo $header_row;
	echo '</thead>';
	echo '<tbody>';

	foreach ($pag->get() as $idx => $row) {
		if (isset($config['get_row_index'])) {
			$print_row($row, $idx);
		} else {
			$print_row($row);
		}
	}
	if ($pag->isEmpty()) {
		echo '<tr><td colspan="233">'.UOJLocale::get('none').'</td></tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
	
	if (isset($config['print_after_table'])) {
		$fun = $config['print_after_table'];
		$fun();
	}
		
	echo $pag->pagination();
}

function getSubmissionStatusDetails($submission) {
	$html = '<td colspan="233" style="vertical-align: middle">';
	
	$out_status = explode(', ', $submission['status'])[0];
	
	$fly = '<img src="/images/utility/qpx_n/b37.gif" alt="小熊像超人一样飞" class="img-rounded" />';
	$think = '<img src="/images/utility/qpx_n/b29.gif" alt="小熊像在思考" class="img-rounded" />';
	
	if ($out_status == 'Judged') {
		$status_text = '<strong>Judged!</strong>';
		$status_img = $fly;
	} else {
		if ($submission['status_details'] !== '') {
			$status_img = $fly;
			$status_text = HTML::escape($submission['status_details']);
		} else {
			$status_img = $think;
			$status_text = $out_status;
		}
	}
	$html .= '<div class="uoj-status-details-img-div">' . $status_img . '</div>';
	$html .= '<div class="uoj-status-details-text-div">' . $status_text . '</div>';

	$html .= '</td>';
	return $html;
}

function echoSubmission($submission, $config, $user) {
	global $REQUIRE_LIB;

	$problem = queryProblemBrief($submission['problem_id']);
	$submitterLink = getUserLink($submission['submitter']);
	
	if ($submission['score'] == null) {
		$used_time_str = "/";
		$used_memory_str = "/";
	} else {
		$used_time_str = $submission['used_time'] . 'ms';
		$used_memory_str = $submission['used_memory'] . 'kb';
	}
	
	$status = explode(', ', $submission['status'])[0];
	
	$show_status_details = Auth::check() && $submission['submitter'] === Auth::id() && $status !== 'Judged';
	
	if (!$show_status_details) {
		echo '<tr>';
	} else {
		echo '<tr class="warning">';
	}
	if (!isset($config['id_hidden'])) {
		echo '<td><a ';
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo ' class="text-decoration-none" ';
		}
		echo ' href="/submission/', $submission['id'], '">#', $submission['id'], '</a></td>';
	}
	if (!isset($config['problem_hidden'])) {
		if ($submission['contest_id']) {
			echo '<td>', getContestProblemLink($problem, $submission['contest_id'], '!id_and_title'), '</td>';
		} else {
			echo '<td>', getProblemLink($problem, '!id_and_title'), '</td>';
		}
	}
	if (!isset($config['submitter_hidden'])) {
		echo '<td>', $submitterLink, '</td>';
	}
	if (!isset($config['result_hidden'])) {
		echo '<td>';
		if ($status == 'Judged') {
			if ($submission['score'] == null) {
				echo '<a ';
		
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo ' class="text-decoration-none small" ';
				} else {
					echo ' class="small" ';
				}
		
				echo ' href="/submission/', $submission['id'], '">', $submission['result_error'], '</a>';
			} else {
				echo '<a href="/submission/', $submission['id'], '" class="uoj-score">', $submission['score'], '</a>';
			}
		} else {
			echo '<a ';
		
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' class="text-decoration-none small" ';
			} else {
				echo ' class="small" ';
			}
	
			echo ' href="/submission/', $submission['id'], '">', $status, '</a>';
		}
		echo '</td>';
	}
	if (!isset($config['used_time_hidden'])) {
		echo '<td>', $used_time_str, '</td>';
	}
	if (!isset($config['used_memory_hidden'])) {
		echo '<td>', $used_memory_str, '</td>';
	}

	echo '<td>', '<a ';
	if (isset($REQUIRE_LIB['bootstrap5'])) {
		echo ' class="text-decoration-none" ';
	}
	echo ' href="/submission/', $submission['id'], '">', $submission['language'], '</a>', '</td>';

	if ($submission['tot_size'] < 1024) {
		$size_str = $submission['tot_size'] . 'b';
	} else {
		$size_str = sprintf("%.1f", $submission['tot_size'] / 1024) . 'kb';
	}
	echo '<td>', $size_str, '</td>';

	if (!isset($config['submit_time_hidden'])) {
		echo '<td><small>', $submission['submit_time'], '</small></td>';
	}
	if (!isset($config['judge_time_hidden'])) {
		echo '<td><small>', $submission['judge_time'], '</small></td>';
	}
	echo '</tr>';
	if ($show_status_details) {
		echo '<tr id="', "status_details_{$submission['id']}", '" class="info">';
		echo getSubmissionStatusDetails($submission);
		echo '</tr>';
		echo '<script type="text/javascript">update_judgement_status_details('.$submission['id'].')</script>';
	}
}


function echoSubmissionsListOnlyOne($submission, $config, $user) {
	global $REQUIRE_LIB;

	if (isset($REQUIRE_LIB['bootstrap5'])) {
		echo '<div class="card mb-3 table-responsive">';
		echo '<table class="table text-center uoj-table mb-0">';
	} else {
		echo '<div class="table-responsive">';
		echo '<table class="table table-bordered table-text-center">';
	}
	echo '<thead>';
	echo '<tr>';
	if (!isset($config['id_hidden'])) {
		echo '<th>ID</th>';
	}
	if (!isset($config['problem_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::problem').'</th>';
	}
	if (!isset($config['submitter_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::submitter').'</th>';
	}
	if (!isset($config['result_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::result').'</th>';
	}
	if (!isset($config['used_time_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::used time').'</th>';
	}
	if (!isset($config['used_memory_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::used memory').'</th>';
	}
	echo '<th>'.UOJLocale::get('problems::language').'</th>';
	echo '<th>'.UOJLocale::get('problems::file size').'</th>';
	if (!isset($config['submit_time_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::submit time').'</th>';
	}
	if (!isset($config['judge_time_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::judge time').'</th>';
	}
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	echoSubmission($submission, $config, $user);
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}


function echoSubmissionsList($cond, $tail, $config, $user) {
	global $REQUIRE_LIB;

	$header_row = '<tr>';
	$col_names = array();
	$col_names[] = 'submissions.status_details';
	$col_names[] = 'submissions.status';
	$col_names[] = 'submissions.result_error';
	$col_names[] = 'submissions.score';
	
	if (!isset($config['id_hidden'])) {
		$header_row .= '<th>ID</th>';
		$col_names[] = 'submissions.id';
	}
	if (!isset($config['problem_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::problem').'</th>';
		$col_names[] = 'submissions.problem_id';
		$col_names[] = 'submissions.contest_id';
	}
	if (!isset($config['submitter_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submitter').'</th>';
		$col_names[] = 'submissions.submitter';
	}
	if (!isset($config['result_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::result').'</th>';
	}
	if (!isset($config['used_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::used time').'</th>';
		$col_names[] = 'submissions.used_time';
	}
	if (!isset($config['used_memory_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::used memory').'</th>';
		$col_names[] = 'submissions.used_memory';
	}
	$header_row .= '<th>'.UOJLocale::get('problems::language').'</th>';
	$col_names[] = 'submissions.language';
	$header_row .= '<th>'.UOJLocale::get('problems::file size').'</th>';
	$col_names[] = 'submissions.tot_size';

	if (!isset($config['submit_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submit time').'</th>';
		$col_names[] = 'submissions.submit_time';
	}
	if (!isset($config['judge_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::judge time').'</th>';
		$col_names[] = 'submissions.judge_time';
	}
	$header_row .= '</tr>';
	
	$table_name = isset($config['table_name']) ? $config['table_name'] : 'submissions';
	
	if (!isProblemManager($user)) {
		if ($user != null) {
			$permission_cond = "submissions.is_hidden = false or submissions.submitter = '{$user['username']}' or (submissions.is_hidden = true and (submissions.problem_id in (select problem_id from problems_permissions where username = '{$user['username']}') or submissions.problem_id in (select id from problems where uploader = '{$user['username']}')))";
		} else {
			$permission_cond = "submissions.is_hidden = false";
		}
		if ($cond !== '1') {
			$cond = "($cond) and ($permission_cond)";
		} else {
			$cond = $permission_cond;
		}
	}
	
	$table_config = isset($config['table_config']) ? $config['table_config'] : null;
	
	echoLongTable($col_names, $table_name, $cond, $tail, $header_row,
		function($submission) use ($config, $user) {
			echoSubmission($submission, $config, $user);
		}, $table_config);
}

function echoSubmissionContent($submission, $requirement) {
	global $REQUIRE_LIB;

	$zip_file = new ZipArchive();
	$submission_content = json_decode($submission['content'], true);
	$zip_file->open(UOJContext::storagePath().$submission_content['file_name']);
	
	$config = array();
	foreach ($submission_content['config'] as $config_key => $config_val) {
		$config[$config_val[0]] = $config_val[1];
	}
	
	foreach ($requirement as $req) {
		if ($req['type'] == "source code") {
			$file_content = $zip_file->getFromName("{$req['name']}.code");
			$file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
			$file_language = htmlspecialchars($config["{$req['name']}_language"]);
			$footer_text = UOJLocale::get('problems::source code').', '.UOJLocale::get('problems::language').': '.$file_language;
			switch ($file_language) {
				case 'C++':
				case 'C++11':
				case 'C++17':
				case 'C++20':
				case 'C++98':
				case 'C++03':
					$sh_class = 'sh_cpp language-cpp';
					break;
				case 'Python2':
				case 'Python2.7':
				case 'Python3':
					$sh_class = 'sh_python language-python';
					break;
				case 'Java8':
				case 'Java11':
				case 'Java17':
					$sh_class = 'sh_java language-java';
					break;
				case 'C':
					$sh_class = 'sh_c language-c';
					break;
				case 'Pascal':
					$sh_class = 'sh_pascal language-pascal';
					break;
				default:
					$sh_class = '';
					break;
			}
			echo '<div class="card border-info mb-3">';
			echo '<div class="card-header bg-info">';
			echo '<h4 class="card-title">'.$req['name'].'</h4>';
			echo '</div>';
			echo '<div class="card-body">';
			echo '<pre><code class="'.$sh_class;
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' bg-light rounded p-3 ';
			}
			echo '">'.$file_content."\n".'</code></pre>';
			echo '</div>';
			echo '<div class="card-footer">'.$footer_text.'</div>';
			echo '</div>';
		} elseif ($req['type'] == "text") {
			$file_content = $zip_file->getFromName("{$req['file_name']}", 504);
			$file_content = strOmit($file_content, 500);
			$file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
			$footer_text = UOJLocale::get('problems::text file');
			echo '<div class="card border-info mb-3">';
			echo '<div class="card-header bg-info">';
			echo '<h4 class="card-title">'.$req['file_name'].'</h4>';
			echo '</div>';
			echo '<div class="card-body">';
			echo '<pre class=" ';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' bg-light rounded p-3 ';
			}
			echo " \">\n".$file_content."\n".'</pre>';
			echo '</div>';
			echo '<div class="card-footer">'.$footer_text.'</div>';
			echo '</div>';
		}
	}

	$zip_file->close();
}


class JudgementDetailsPrinter {
	private $name;
	private $styler;
	private $dom;
	
	private $subtask_num;

	private function _print_c($node) {
		foreach ($node->childNodes as $child) {
			if ($child->nodeName == '#text') {
				echo htmlspecialchars($child->nodeValue);
			} else {
				$this->_print($child);
			}
		}
	}
	private function _print($node) {
		global $REQUIRE_LIB;

		if ($node->nodeName == 'error') {
			echo '<pre class=" ';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' bg-light rounded p-3 ';
			}
			echo " \">\n";
			$this->_print_c($node);
			echo "\n</pre>";
		} elseif ($node->nodeName == 'tests') {
			echo '<div id="', $this->name, '_details_accordion">';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				$this->_print_c($node);
			}
			if ($this->styler->show_small_tip) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<div class="my-2 px-2 text-end text-muted">';
				} else {
					echo '<div class="text-right text-muted">';
				}
				echo '小提示：点击横条可展开更详细的信息', '</div>';
			} elseif ($this->styler->ioi_contest_is_running) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<div class="my-2 px-2 text-end text-muted">';
				} else {
					echo '<div class="text-right text-muted">';
				}
				echo 'IOI赛制比赛中不支持显示详细信息', '</div>';
			}
			if (!isset($REQUIRE_LIB['bootstrap5'])) {
				$this->_print_c($node);
			}
			echo '</div>';
		} elseif ($node->nodeName == 'subtask') {
			$subtask_num = $node->getAttribute('num');
			$subtask_score = $node->getAttribute('score');
			$subtask_info = $node->getAttribute('info');
			
			echo '<div class="card ', $this->styler->getTestInfoClass($subtask_info);
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' border-0 rounded-0 border-bottom ';
			} else {
				echo ' mb-3 ';
			}
			echo '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			$accordion_collapse =  "{$accordion_parent}_collapse_subtask_{$subtask_num}";
			$accordion_collapse_accordion =  "{$accordion_collapse}_accordion";
			
			echo '<div class="card-header ';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' uoj-submission-result-item bg-transparent rounded-0 border-0 ';
			}
			echo '" ';

			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' data-bs-toggle="collapse" data-bs-parent="#', $accordion_parent, '" data-bs-target="#', $accordion_collapse, '" ';
			} else {
				echo ' data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '" ';
			}
			echo ' >';

			echo 		'<div class="row">';
			echo 			'<div class="col-sm-4">';

			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<h3 class="fs-5">';
			} else {
				echo '<h3 class="card-title">';
			}

			echo 				'Subtask #', $subtask_num, ': ', '</h3>';
			echo 			'</div>';
			
			if ($this->styler->show_score) {
				echo 		'<div class="col-sm-2">';

				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<i class="bi bi-clipboard-check"></i> ';
				} else {
					echo 'score: ';
				}

				echo $subtask_score;

				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo ' pts';
				}

				echo 		'</div>';
				echo 		'<div class="col-sm-2 uoj-status-text">';
				
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo $this->styler->getTestInfoIcon($subtask_info);
				}

				echo 			htmlspecialchars($subtask_info);
				echo 		'</div>';
			} else {
				echo 		'<div class="col-sm-4">';

				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo $this->styler->getTestInfoIcon($subtask_info);
				}

				echo 			htmlspecialchars($subtask_info);
				echo 		'</div>';
			}

			echo 		'</div>';
			echo 	'</div>';
			
			echo 	'<div id="', $accordion_collapse, '" class="card-collapse collapse">';
			echo 		'<div class="card-body ';
			
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' pt-0 ';
			}
			echo '">';

			echo 			'<div id="', $accordion_collapse_accordion, '" ';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' class="border rounded overflow-hidden" ';
			}
			echo ' >';
			$this->subtask_num = $subtask_num;
			$this->_print_c($node);
			$this->subtask_num = null;
			echo 			'</div>';

			echo 		'</div>';
			echo 	'</div>';
			echo '</div>';
		} elseif ($node->nodeName == 'test') {
			$test_info = $node->getAttribute('info');
			$test_num = $node->getAttribute('num');
			$test_score = $node->getAttribute('score');
			$test_time = $node->getAttribute('time');
			$test_memory = $node->getAttribute('memory');

			echo '<div class="card ', $this->styler->getTestInfoClass($test_info);
			
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' border-0 rounded-0 border-bottom ';
			} else {
				echo ' mb-3 ';
			}
			echo '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			if ($this->subtask_num != null) {
				$accordion_parent .= "_collapse_subtask_{$this->subtask_num}_accordion";
			}
			$accordion_collapse = "{$accordion_parent}_collapse_test_{$test_num}";

			echo '<div class="card-header ';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' uoj-submission-result-item bg-transparent rounded-0 border-0 ';
			}
			echo '" ';
			if (!$this->styler->shouldFadeDetails($test_info)) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo ' data-bs-toggle="collapse" data-bs-parent="#', $accordion_parent, '" data-bs-target="#', $accordion_collapse, '" ';
				} else {
					echo ' data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '" ';
				}
			}
			echo '>';
			echo '<div class="row">';
			echo '<div class="col-sm-4">';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<h4 class="fs-5">';
			} else {
				echo '<h4 class="card-title">';
			}
			if ($test_num > 0) {
				echo 'Test #', $test_num, ': ';
			} else {
				echo 'Extra Test:';
			}
			echo '</h4>';
			echo '</div>';
				
			if ($this->styler->show_score) {
				echo '<div class="col-sm-2">';
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<i class="bi bi-clipboard-check"></i> ';
				} else {
					echo 'score: ';
				}
				echo $test_score;
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo ' pts';
				}
				echo '</div>';
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<div class="col-sm-2 uoj-status-text">';

					echo $this->styler->getTestInfoIcon($test_info);
				} else {
					echo '<div class="col-sm-2">';
				}
				echo htmlspecialchars($test_info);
				echo '</div>';
			} else {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<div class="col-sm-4 uoj-status-text">';

					echo $this->styler->getTestInfoIcon($test_info);
				} else {
					echo '<div class="col-sm-4">';
				}
				echo htmlspecialchars($test_info);
				echo '</div>';
			}

			echo '<div class="col-sm-2">';
			if ($test_time >= 0) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<i class="bi bi-hourglass-split"></i> ';
				} else {
					echo 'time: ';
				}
				echo $test_time, ' ms';
			}
			echo '</div>';

			echo '<div class="col-sm-2">';
			if ($test_memory >= 0) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<i class="bi bi-memory"></i> ';
				} else {
					echo 'memory: ';
				}
				echo $test_memory, ' kB';
			}
			echo '</div>';

			echo '</div>';
			echo '</div>';

			if (!$this->styler->shouldFadeDetails($test_info)) {
				$accordion_collapse_class = 'card-collapse collapse';
				if ($this->styler->collapse_in) {
					$accordion_collapse_class .= ' in';
				}
				echo '<div id="', $accordion_collapse, '" class="uoj-testcase ', $accordion_collapse_class, '" data-test=' . $test_num . '>';
				echo '<div class="card-body">';

				$this->_print_c($node);

				echo '</div>';
				echo '</div>';
			}

			echo '</div>';
		} elseif ($node->nodeName == 'custom-test') {
			$test_info = $node->getAttribute('info');
			$test_time = $node->getAttribute('time');
			$test_memory = $node->getAttribute('memory');

			echo '<div class="card ', $this->styler->getTestInfoClass($test_info);
			if (isset($REQUIRE_LIB['bootstrap5'])) {
			} else {
			}
			echo ' mb-3">';

			$accordion_parent = "{$this->name}_details_accordion";
			$accordion_collapse = "{$accordion_parent}_collapse_custom_test";
			
			echo '<div class="card-header ';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' uoj-submission-result-item bg-transparent rounded-0 border-0 ';
			}
			echo '" ';
			if (!$this->styler->shouldFadeDetails($test_info)) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo ' data-bs-toggle="collapse" data-bs-parent="#', $accordion_parent, '" data-bs-target="#', $accordion_collapse, '" ';
				} else {
					echo ' data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '" ';
				}
			}
			echo '>';

			echo '<div class="row">';
			echo '<div class="col-sm-4">';
			echo '<h4 class="card-title">', 'Custom Test: ', '</h4>';
			echo '</div>';

			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<div class="col-sm-4 uoj-status-text">';

				echo $this->styler->getTestInfoIcon($test_info);
			} else {
				echo '<div class="col-sm-4">';
			}
			echo htmlspecialchars($test_info);
			echo '</div>';
				
			echo '<div class="col-sm-2">';
			if ($test_time >= 0) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<i class="bi bi-hourglass-split"></i> ';
				} else {
					echo 'time: ';
				}
				echo $test_time, ' ms';
			}
			echo '</div>';

			echo '<div class="col-sm-2">';
			if ($test_memory >= 0) {
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					echo '<i class="bi bi-memory"></i> ';
				} else {
					echo 'memory: ';
				}
				echo $test_memory, ' kB';
			}
			echo '</div>';

			echo '</div>';
			echo '</div>';

			if (!$this->styler->shouldFadeDetails($test_info)) {
				$accordion_collapse_class = 'card-collapse collapse';
				if ($this->styler->collapse_in) {
					$accordion_collapse_class .= ' in';
				}
				echo '<div id="', $accordion_collapse, '" class="', $accordion_collapse_class, '">';
				echo '<div class="card-body">';

				$this->_print_c($node);

				echo '</div>';
				echo '</div>';

				echo '</div>';
			}
		} elseif ($node->nodeName == 'in') {
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<h4 class="fs-6 d-flex justify-content-between"><span>input: </span><a class="uoj-testcase-download-input"></a></h4>';
			} else {
				echo '<h4>input: <a class="uoj-testcase-download-input"></a></h4>';
			}
			echo "<pre class=\"bg-light p-3 rounded\">\n";
			$this->_print_c($node);
			echo "\n</pre>";
		} elseif ($node->nodeName == 'out') {
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<h4 class="fs-6 d-flex justify-content-between"><span>output: </span><a class="uoj-testcase-download-output"></a></h4>';
			} else {
				echo '<h4>output: <a class="uoj-testcase-download-output"></a></h4>';
			}
			echo "<pre class=\"bg-light p-3 rounded\">\n";
			$this->_print_c($node);
			echo "\n</pre>";
		} elseif ($node->nodeName == 'res') {
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<h4 class="fs-6 d-flex justify-content-between"><span>result: </span><a class="uoj-testcase-download-result"></a></h4>';
			} else {
				echo '<h4>result: <a class="uoj-testcase-download-result"></a></h4>';
			}
			echo "<pre class=\"bg-light p-3 rounded\">\n";
			$this->_print_c($node);
			echo "\n</pre>";
		} else {
			echo '<', $node->nodeName;
			foreach ($node->attributes as $attr) {
				echo ' ', $attr->name, '="', htmlspecialchars($attr->value), '"';
			}
			echo '>';
			$this->_print_c($node);
			echo '</', $node->nodeName, '>';
		}
	}

	public function __construct($details, $styler, $name) {
		global $REQUIRE_LIB;

		$this->name = $name;
		$this->styler = $styler;
		$this->details = $details;
		$this->dom = new DOMDocument();
		if (!$this->dom->loadXML($this->details)) {
			throw new Exception("XML syntax error");
		}
		$this->details = '';
	}
	public function printHTML() {
		global $REQUIRE_LIB;

		$this->subtask_num = null;
		$this->_print($this->dom->documentElement);
	}
}

function echoJudgementDetails($raw_details, $styler, $name) {
	global $REQUIRE_LIB;

	try {
		$printer = new JudgementDetailsPrinter($raw_details, $styler, $name);
		$printer->printHTML();
	} catch (Exception $e) {
		echo 'Failed to show details';
	}
}

class SubmissionDetailsStyler {
	public $show_score = true;
	public $show_small_tip = true;
	public $collapse_in = false;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info == 'Accepted' || $info == 'Extra Test Passed') {
			return 'card-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'card-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'card-uoj-acceptable-answer';
		} else {
			return 'card-uoj-wrong';
		}
	}
	public function getTestInfoIcon($test_info) {
		if ($test_info == 'Accepted' || $test_info == 'Extra Test Passed') {
			return '<i class="bi bi-check-lg"></i> ';
		} elseif ($test_info == 'Time Limit Exceeded') {
			return '<i class="bi bi-clock"></i> ';
		} elseif ($test_info == 'Acceptable Answer') {
			return '<i class="bi bi-dash-square"></i> ';
		} elseif ($test_info == 'Wrong Answer') {
			return '<i class="bi bi-x-lg"></i> ';
		} else {
			return '<i class="bi bi-slash-circle"></i> ';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details || $info == 'Extra Test Passed';
	}
}
class CustomTestSubmissionDetailsStyler {
	public $show_score = true;
	public $show_small_tip = false;
	public $collapse_in = true;
	public $fade_all_details = false;
	public $ioi_contest_is_running = false;
	public function getTestInfoClass($info) {
		if ($info == 'Success') {
			return 'card-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'card-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'card-uoj-acceptable-answer';
		} else {
			return 'card-uoj-wrong';
		}
	}
	public function getTestInfoIcon($test_info) {
		if ($test_info == 'Success') {
			return '<i class="bi bi-check-lg"></i> ';
		} elseif ($test_info == 'Time Limit Exceeded') {
			return '<i class="bi bi-clock"></i> ';
		} elseif ($test_info == 'Acceptable Answer') {
			return '<i class="bi bi-dash-square"></i> ';
		} elseif ($test_info == 'Wrong Answer') {
			return '<i class="bi bi-x-lg"></i> ';
		} else {
			return '<i class="bi bi-slash-circle"></i> ';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details;
	}
}
class HackDetailsStyler {
	public $show_score = false;
	public $show_small_tip = false;
	public $collapse_in = true;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info == 'Accepted' || $info == 'Extra Test Passed') {
			return 'card-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'card-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'card-uoj-acceptable-answer';
		} else {
			return 'card-uoj-wrong';
		}
	}
	public function getTestInfoIcon($test_info) {
		if ($test_info == 'Accepted' || $test_info == 'Extra Test Passed') {
			return '<i class="bi bi-check-lg"></i> ';
		} elseif ($test_info == 'Time Limit Exceeded') {
			return '<i class="bi bi-clock"></i> ';
		} elseif ($test_info == 'Acceptable Answer') {
			return '<i class="bi bi-dash-square"></i> ';
		} elseif ($test_info == 'Wrong Answer') {
			return '<i class="bi bi-x-lg"></i> ';
		} else {
			return '<i class="bi bi-slash-circle"></i> ';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details;
	}
}

function echoSubmissionDetails($submission_details, $name) {
	global $REQUIRE_LIB;
	
	echoJudgementDetails($submission_details, new SubmissionDetailsStyler(), $name);
}
function echoCustomTestSubmissionDetails($submission_details, $name) {
	echoJudgementDetails($submission_details, new CustomTestSubmissionDetailsStyler(), $name);
}
function echoHackDetails($hack_details, $name) {
	global $REQUIRE_LIB;
	
	echoJudgementDetails($hack_details, new HackDetailsStyler(), $name);
}

function echoHack($hack, $config, $user) {
	global $REQUIRE_LIB;

	$problem = queryProblemBrief($hack['problem_id']);
	echo '<tr>';
	if (!isset($config['id_hidden'])) {
		echo '<td><a ';
		
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo ' class="text-decoration-none" ';
		}

		echo ' href="/hack/', $hack['id'], '">#', $hack['id'], '</a></td>';
	}
	if (!isset($config['submission_hidden'])) {
		echo '<td><a ';
		
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo ' class="text-decoration-none" ';
		}

		echo ' href="/submission/', $hack['submission_id'], '">#', $hack['submission_id'], '</a></td>';
	}
	if (!isset($config['problem_hidden'])) {
		if ($hack['contest_id']) {
			echo '<td>', getContestProblemLink($problem, $hack['contest_id'], '!id_and_title'), '</td>';
		} else {
			echo '<td>', getProblemLink($problem, '!id_and_title'), '</td>';
		}
	}
	if (!isset($config['hacker_hidden'])) {
		echo '<td>', getUserLink($hack['hacker']), '</td>';
	}
	if (!isset($config['owner_hidden'])) {
		echo '<td>', getUserLink($hack['owner']), '</td>';
	}
	if (!isset($config['result_hidden'])) {
		if ($hack['judge_time'] == null) {
			echo '<td><a ';
		
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' class="text-decoration-none" ';
			}
	
			echo ' href="/hack/', $hack['id'], '">Waiting</a></td>';
		} elseif ($hack['success'] == null) {
			echo '<td><a ';
		
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' class="text-decoration-none" ';
			}
	
			echo ' href="/hack/', $hack['id'], '">Judging</a></td>';
		} elseif ($hack['success']) {
			echo '<td><a href="/hack/', $hack['id'], '" class="uoj-status ';
		
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' text-decoration-none ';
			}
	
			echo ' " data-success="1"><strong>Success!</strong></a></td>';
		} else {
			echo '<td><a href="/hack/', $hack['id'], '" class="uoj-status ';
		
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' text-decoration-none ';
			}
	
			echo ' " data-success="0"><strong>Failed.</strong></a></td>';
		}
	} else {
		echo '<td>Hidden</td>';
	}
	if (!isset($config['submit_time_hidden'])) {
		echo '<td>', $hack['submit_time'], '</td>';
	}
	if (!isset($config['judge_time_hidden'])) {
		echo '<td>', $hack['judge_time'], '</td>';
	}
	echo '</tr>';
}
function echoHackListOnlyOne($hack, $config, $user) {
	global $REQUIRE_LIB;
	
	if (isset($REQUIRE_LIB['bootstrap5'])) {
		echo '<div class="card mb-3 table-responsive">';
		echo '<table class="table text-center uoj-table mb-0">';
	} else {
		echo '<div class="table-responsive">';
		echo '<table class="table table-bordered table-text-center">';
	}
	echo '<thead>';
	echo '<tr>';
	if (!isset($config['id_hidden'])) {
		echo '<th>ID</th>';
	}
	if (!isset($config['submission_id_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::submission id').'</th>';
	}
	if (!isset($config['problem_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::problem').'</th>';
	}
	if (!isset($config['hacker_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::hacker').'</th>';
	}
	if (!isset($config['owner_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::owner').'</th>';
	}
	if (!isset($config['result_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::result').'</th>';
	}
	if (!isset($config['submit_time_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::submit time').'</th>';
	}
	if (!isset($config['judge_time_hidden'])) {
		echo '<th>'.UOJLocale::get('problems::judge time').'</th>';
	}
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	echoHack($hack, $config, $user);
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}
function echoHacksList($cond, $tail, $config, $user) {
	$header_row = '<tr>';
	$col_names = array();
	
	$col_names[] = 'id';
	$col_names[] = 'success';
	$col_names[] = 'judge_time';
	
	if (!isset($config['id_hidden'])) {
		$header_row .= '<th>ID</th>';
	}
	if (!isset($config['submission_id_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submission id').'</th>';
		$col_names[] = 'submission_id';
	}
	if (!isset($config['problem_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::problem').'</th>';
		$col_names[] = 'problem_id';
	}
	if (!isset($config['hacker_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::hacker').'</th>';
		$col_names[] = 'hacker';
	}
	if (!isset($config['owner_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::owner').'</th>';
		$col_names[] = 'owner';
	}
	if (!isset($config['result_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::result').'</th>';
	}
	if (!isset($config['submit_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submit time').'</th>';
		$col_names[] = 'submit_time';
	}
	if (!isset($config['judge_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::judge time').'</th>';
	}
	$header_row .= '</tr>';

	if (!isSuperUser($user)) {
		if ($user != null) {
			$permission_cond = "is_hidden = false or (is_hidden = true and problem_id in (select problem_id from problems_permissions where username = '{$user['username']}'))";
		} else {
			$permission_cond = "is_hidden = false";
		}
		if ($cond !== '1') {
			$cond = "($cond) and ($permission_cond)";
		} else {
			$cond = $permission_cond;
		}
	}

	$table_config = isset($config['table_config']) ? $config['table_config'] : null;
	
	echoLongTable($col_names, 'hacks', $cond, $tail, $header_row,
		function($hacks) use ($config, $user) {
			echoHack($hacks, $config, $user);
		}, $table_config);
}

function echoBlog($blog, $config = array()) {
	global $REQUIRE_LIB;

	$default_config = array(
		'blog' => $blog,
		'show_title_only' => false,
		'is_preview' => false
	);
	foreach ($default_config as $key => $val) {
		if (!isset($config[$key])) {
			$config[$key] = $val;
		}
	}

	$config['REQUIRE_LIB'] = $REQUIRE_LIB;

	uojIncludeView('blog-preview', $config);
}
function echoBlogTag($tag) {
	global $REQUIRE_LIB;

	if (isset($REQUIRE_LIB['bootstrap5'])) {
		echo '<a class="uoj-blog-tag my-1">';
		echo '<span class="badge bg-secondary">';
	} else {
		echo '<a class="uoj-blog-tag">';
		echo '<span class="badge badge-pill badge-secondary">';
	}
	echo HTML::escape($tag), '</span></a>';
}

function echoUOJPageHeader($page_title, $extra_config = array()) {
	global $REQUIRE_LIB;
	$config = UOJContext::pageConfig();
	$config['REQUIRE_LIB'] = $REQUIRE_LIB;
	$config['PageTitle'] = $page_title;
	$config = array_merge($config, $extra_config);
	uojIncludeView('page-header', $config);
}
function echoUOJPageFooter($config = array()) {
	global $REQUIRE_LIB;
	$config['REQUIRE_LIB'] = $REQUIRE_LIB;

	uojIncludeView('page-footer', $config);
}

function echoRanklist($config = array()) {
	global $REQUIRE_LIB;

	$header_row = '';
	$header_row .= '<tr>';
	$header_row .= '<th style="width: 5em;">#</th>';
	$header_row .= '<th style="width: 14em;">'.UOJLocale::get('username').'</th>';
	$header_row .= '<th style="width: 50em;">'.UOJLocale::get('motto').'</th>';
	$header_row .= '<th style="width: 5em;">'.UOJLocale::get('solved').'</th>';
	$header_row .= '</tr>';

	$purifier = HTML::purifier_inline();
	$users = array();
	$print_row = function($user, $now_cnt) use (&$users, $config, $purifier) {
		if (!$users) {
			if ($now_cnt == 1) {
				$rank = 1;
			} else {
				$rank = DB::selectCount("select count(*) from (select b.username as username, count(*) as accepted from best_ac_submissions a inner join user_info b on a.submitter = b.username group by username) as derived where accepted > {$user['ac_num']}") + 1;
			}
		} else {
			$rank = $now_cnt;
		}

		$user['rank'] = $rank;

		echo '<tr>';
		echo '<td>' . $user['rank'] . '</td>';
		echo '<td>' . getUserLink($user['username']) . '</td>';
		echo "<td>";
		echo $purifier->purify($user['motto']);
		echo "</td>";
		echo '<td>' . $user['ac_num'] . '</td>';
		echo '</tr>';
		
		$users[] = $user;
	};

	$from = 'best_ac_submissions a inner join user_info b on a.submitter = b.username';
	$col_names = array('b.username as username', 'count(*) as ac_num', 'b.motto as motto');
	$cond = '1';
	$tail = 'group by username order by ac_num desc, username asc';

	if (isset($config['group_id'])) {
		$group_id = $config['group_id'];
		$from = "best_ac_submissions a inner join user_info b on a.submitter = b.username inner join groups_users c on (a.submitter = c.username and c.group_id = {$group_id})";
	}

	if (isset($config['top10'])) {
		$tail .= ' limit 10';
	}

	$config['get_row_index'] = '';
	$config['pagination_table'] = 'user_info';
	echoLongTable($col_names, $from, $cond, $tail, $header_row, $print_row, $config);
}
