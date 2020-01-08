<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

DROP TABLE IF EXISTS `cdb_lmt_rename_log`;

EOF;

runquery($sql);

$finish = true;
