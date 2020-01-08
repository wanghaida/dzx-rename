<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

// 检测用户
if(empty($_G['uid'])) {
	showmessage('to_login', '', [], ['showmsg' => true, 'login' => 1]);
}

// 读取配置
$lmt_conf = $_G['cache']['plugin']['lmt_rename'];
$lmt_conf['groups'] = unserialize($lmt_conf['groups']);

// 读取积分
$credit_number = $lmt_conf['credit_unit'] ? getuserprofile('extcredits'.$lmt_conf['credit_unit']) : 0;

// 读取允许用户组名称和总数量
foreach ($lmt_conf['groups'] as $k => $v) {
	$groups_title[] = $_G['cache']['usergroups'][$v]['grouptitle'];
}
$groups_number = count($groups_title);

// 处理请求
if (submitcheck('formhash') && !defined('IN_MOBILE')) {
	// 判定允许用户组
	if (!in_array($_G['member']['groupid'], $lmt_conf['groups'])) {
		showmessage('抱歉，您当前所在的用户组没有权限使用修改用户名功能', '', [], ['handle' => false]);
	}
	// 判断消耗积分项
	if ($lmt_conf['credit_unit'] && $lmt_conf['credit_number']) {
		if ($credit_number < $lmt_conf['credit_number']) {
			showmessage('抱歉，您的积分不足无法使用修改用户名功能', '', [], ['handle' => false]);
		}
	}
	// 判断允许修改次数
	if ($lmt_conf['times']) {
		$times = DB::result_first('SELECT count(`id`) FROM `%t` WHERE `uid` = %s', ['lmt_rename_log', $_G['uid']]);
		if ($times >= $lmt_conf['times']) {
			showmessage('抱歉，超过允许修改次数', '', [], ['handle' => false]);
		}
	}
	// 判断修改时间间隔
	if ($lmt_conf['time']) {
		$time = DB::fetch_first('SELECT `dateline` FROM `%t` WHERE `uid` = %s ORDER BY `dateline` DESC LIMIT 1', ['lmt_rename_log', $_G['uid']]);
		if ($time['dateline'] && ($_G['timestamp'] - $time['dateline'] < 60 * 60 * $lmt_conf['time'])) {
			showmessage('抱歉，修改时间间隔不足，请其他时间再试', '', [], ['handle' => false]);
		}
	}

	$oldusername = $_G['username'];
	$newusername = addslashes(dhtmlspecialchars($_GET['newusername'], ENT_COMPAT, 'utf-8'));

	$userid  = $_G['uid'];
	$userlen = dstrlen($newusername);

	// 判断新用户名
	if ($userlen < 3) {
		showmessage('profile_username_tooshort', '', [], ['handle' => false]);
	} elseif ($userlen > 15) {
		showmessage('profile_username_toolong', '', [], ['handle' => false]);
	}
	// 判断新用户名 by UC
	loaducenter();
	$ucresult = uc_user_checkname($newusername);
	if ($ucresult != '1') {
		$ucerrors = [
			'-1' => 'profile_username_illegal',
			'-2' => 'profile_username_protect',
			'-3' => 'register_activation',
		];
		showmessage($ucerrors[$ucresult], '', [], ['handle' => false]);
	}

	// 修改用户名
	changeusername($userid, $oldusername, $newusername);

	// 修改积分
	if ($lmt_conf['credit_unit'] && $lmt_conf['credit_number']) {
		updatemembercount($userid, [$lmt_conf['credit_unit'] => '-'.$lmt_conf['credit_number']], true, '', $userid, '修改用户名', '修改用户名', '修改用户名');
	}

	// 记录日志
	DB::insert('lmt_rename_log', [
		'uid'           => $userid,
		'credit_unit'   => $lmt_conf['credit_unit'],
		'credit_number' => $lmt_conf['credit_number'],
		'oldusername'   => $oldusername,
		'newusername'   => $newusername,
		'dateline'      => $_G['timestamp'],
		'ip'            => $_G['clientip'],
		'ua'            => $_SERVER['HTTP_USER_AGENT'],
	]);
	// 清空缓存
	C::memory()->clear();

	showmessage('profile_succeed', '', [], ['alert' => 'right', 'handle' => false]);
}

function changeusername($userid, $oldusername, $newusername) {
	$member = DB::fetch_first('SELECT `uid` FROM `%t` WHERE `username` = %s', ['common_member', $oldusername]);
	if (empty($member)) return;

	// table => [where, set]，uc.php 中 renameuser 部分
	$tables1 = [
		'common_block'              => ['uid', 'username'],
		'common_invite'             => ['fuid', 'fusername'],
		'common_member_verify_info' => ['uid', 'username'],
		'common_mytask'             => ['uid', 'username'],
		'common_report'             => ['uid', 'username'],
		'forum_thread'              => ['authorid', 'author'],
		'forum_activityapply'       => ['uid', 'username'],
		'forum_groupuser'           => ['uid', 'username'],
		'forum_pollvoter'           => ['uid', 'username'],
		'forum_post'                => ['authorid', 'author'],
		'forum_postcomment'         => ['authorid', 'author'],
		'forum_ratelog'             => ['uid', 'username'],
		'home_album'                => ['uid', 'username'],
		'home_blog'                 => ['uid', 'username'],
		'home_clickuser'            => ['uid', 'username'],
		'home_docomment'            => ['uid', 'username'],
		'home_doing'                => ['uid', 'username'],
		'home_feed'                 => ['uid', 'username'],
		'home_feed_app'             => ['uid', 'username'],
		'home_friend'               => ['fuid', 'fusername'],
		'home_friend_request'       => ['fuid', 'fusername'],
		'home_notification'         => ['authorid', 'author'],
		'home_pic'                  => ['uid', 'username'],
		'home_poke'                 => ['fromuid', 'fromusername'],
		'home_share'                => ['uid', 'username'],
		'home_show'                 => ['uid', 'username'],
		'home_specialuser'          => ['uid', 'username'],
		'home_visitor'              => ['vuid', 'vusername'],
		'portal_article_title'      => ['uid', 'username'],
		'portal_comment'            => ['uid', 'username'],
		'portal_topic'              => ['uid', 'username'],
		'portal_topic_pic'          => ['uid', 'username'],
	];
	// table => [where, set]，补充部分
	$tables2 = [
		'common_adminnote'           => ['admin', 'admin'],
		'common_block_item'          => ['title', 'title'],
		'common_block_item_data'     => ['title', 'title'],
		'common_card_log'            => ['username', 'username'],
		'common_failedlogin'         => ['username', 'username'],
		'common_grouppm'             => ['author', 'author'],
		'common_member'              => ['username', 'username'],
		'common_member_security'     => ['username', 'username'],
		'common_member_validate'     => ['admin', 'admin'],
		'common_session'             => ['username', 'username'],
		'common_word'                => ['admin', 'admin'],
		'forum_announcement'         => ['author', 'author'],
		'forum_collection'           => ['username', 'username'],
		'forum_collectioncomment'    => ['username', 'username'],
		'forum_collectionfollow'     => ['username', 'username'],
		'forum_collectionteamworker' => ['username', 'username'],
		'forum_forumrecommend'       => ['author', 'author'],
		'forum_imagetype'            => ['name', 'name'],
		'forum_order'                => ['buyer', 'buyer'],
		'forum_promotion'            => ['username', 'username'],
		'forum_rsscache'             => ['author', 'author'],
		'forum_threadmod'            => ['username', 'username'],
		'forum_trade'                => ['seller', 'seller'],
		'forum_tradelog'             => ['seller', 'seller'],
		'forum_warning'              => ['author', 'author'],
		'home_comment'               => ['author', 'author'],
		'home_follow'                => ['username', 'username'],
		'home_follow_feed'           => ['username', 'username'],
		'home_follow_feed_archiver'  => ['username', 'username'],
		'portal_rsscache'            => ['author', 'author'],
	];
	// table => [where, set]，补充部分（表名重复）
	$tables3 = [
		'forum_order'    => ['admin', 'admin'],
		'forum_tradelog' => ['buyer', 'buyer'],
		'home_follow'    => ['fusername', 'fusername'],
	];
	// table => [where, set]，UCenter 部分
	$tables4 = [
		'admins'           => ['username', 'username'],
		'badwords'         => ['admin', 'admin'],
		'feeds'            => ['username', 'username'],
		'members'          => ['username', 'username'],
		'mergemembers'     => ['username', 'username'],
		'protectedmembers' => ['username', 'username'],
	];
	// table => [where, set]，UCenter 部分（表名重复）
	$tables5 = [
		'protectedmembers' => ['admin', 'admin'],
	];
	// [table, where, set]，扩展部分，这样设计是防止表名重复
	$tables6 = [];

	foreach($tables1 as $table => $conf) {
		DB::query('UPDATE `%t` SET %i WHERE %i', [$table, "`$conf[1]`='$newusername'", "`$conf[0]`=$userid"]);
	}
	foreach($tables2 as $table => $conf) {
		DB::query('UPDATE `%t` SET %i WHERE %i', [$table, "`$conf[1]`='$newusername'", "`$conf[0]`='$oldusername'"]);
	}
	foreach($tables3 as $table => $conf) {
		DB::query('UPDATE `%t` SET %i WHERE %i', [$table, "`$conf[1]`='$newusername'", "`$conf[0]`='$oldusername'"]);
	}
	foreach($tables4 as $table => $conf) {
		DB::query("UPDATE ".(UC_DBTABLEPRE.$table)." SET `$conf[1]`='$newusername' WHERE `$conf[0]`='$oldusername'");
	}
	foreach($tables5 as $table => $conf) {
		DB::query("UPDATE ".(UC_DBTABLEPRE.$table)." SET `$conf[1]`='$newusername' WHERE `$conf[0]`='$oldusername'");
	}

	global $lmt_conf;
	if ($lmt_conf['tables']) {
		$tables = explode("\n", $lmt_conf['tables']);
		foreach($tables as $v){
			$table = explode(',', $v);
			if (count($table) == 3) {
				$tables6[] = $table;
			}
		}
	}

	foreach($tables6 as $conf) {
		DB::query("UPDATE ".$conf[0]." SET `$conf[2]`='$newusername' WHERE `$conf[1]`='$userid'");
	}
}
