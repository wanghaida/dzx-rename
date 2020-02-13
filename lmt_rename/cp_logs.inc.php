<?php

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

// 删除操作
if ($_GET['op'] == 'delete') {
	C::t('#lmt_rename#lmt_rename')->delete_by_id($_GET['id']);
	ajaxshowheader();
	echo '- 已删除 -';
	ajaxshowfooter();
}

// 每页条数
$ppp = 100;
// 当前页数
$page = max(1, intval($_GET['page']));
// sql
$srchadd = '';
// 搜索内容
$searchtext = '';
// 插件地址
$pluginurl = ADMINSCRIPT.'?action=plugins&operation=config&do='.$pluginid.'&identifier=lmt_rename&pmod=cp_logs';

if (!empty($_GET['srchuid'])) { // 通过 uid 搜索
	$srchuid = intval($_GET['srchuid']);
	$member = getuserbyuid($srchuid);

	$srchadd = "AND uid='".$srchuid."'";
	$searchtext = '搜索 "'.$member['username'].'" 的记录';
} elseif (!empty($_GET['srchusername'])) { // 通过 username 搜索
	$srchadd = "AND oldusername='".addslashes($_GET['srchusername'])."' OR newusername='".addslashes($_GET['srchusername'])."'";
	$searchtext = '搜索 "'.$_GET['srchusername'].'" 的记录';
}

if ($searchtext) {
	$searchtext = '<a href="'.$pluginurl.'">查看全部</a>&nbsp'.$searchtext;
}

// 表格头
showtableheader();

// 表单头
showformheader('plugins&operation=config&do='.$pluginid.'&identifier=lmt_rename&pmod=cp_logs', 'renamesubmit');
// 表单
showsubmit('renamesubmit', '搜索', $lang['username'].': <input name="srchusername" value="'.htmlspecialchars($_GET['srchusername']).'" class="txt" />', $searchtext);
// 表单尾
showformfooter();

// 记录列表变量
$count = C::t('#lmt_rename#lmt_rename')->count_by_search($srchadd);
$logs = C::t('#lmt_rename#lmt_rename')->fetch_all_by_search($srchadd, ($page - 1) * $ppp, $ppp);
$uids = [];
foreach($logs as $log) {
	$uids[] = $log['uid'];
}
$users = C::t('common_member')->fetch_all($uids);
// 记录列表表头
echo '
	<tr class="header">
		<th>ID</th>
		<th>旧用户名</th>
		<th>新用户名</th>
		<th>消耗积分</th>
		<th>修改时间</th>
		<th></th>
	</tr>
';
// 记录列表内容
foreach($logs as $log) {
	$credit_unit = $_G['setting']['extcredits'][$log['credit_unit']];
	$credit_number = $log['credit_unit'] ? $log['credit_number'] . ' ' . $credit_unit['unit'] . $credit_unit['title'] : '';
	echo '
		<tr>
			<td>
				<a href="'.$pluginurl.'&srchuid='.$log['uid'].'">
					'.$users[$log['uid']]['username'].'('.$log['uid'].')
				</a>
			</td>
			<td>
				<a href="'.$pluginurl.'&srchusername='.rawurlencode($log['oldusername']).'">
					'.$log['oldusername'].'
				</a>
			</td>
			<td>
				<a href="'.$pluginurl.'&srchusername='.rawurlencode($log['newusername']).'">
					'.$log['newusername'].'
				</a>
			</td>
			<td>
				'.$credit_number.'
			</td>
			<td>
				'.date('Y-m-d H:i:s', $log['dateline']).'
			</td>
			<td>
				<a id="p'.$log['id'].'" onclick="ajaxget(this.href, this.id, \'\');return false" href="'.$pluginurl.'&id='.$log['id'].'&op=delete">['.$lang['delete'].']</a>
			</td>
		</tr>
	';
}

// 表格尾
showtablefooter();

// 分页
echo multi($count, $ppp, $page, ADMINSCRIPT."?action=plugins&operation=config&do=$pluginid&identifier=lmt_rename&pmod=cp_logs");
