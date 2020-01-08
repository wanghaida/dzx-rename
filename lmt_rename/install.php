<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

DROP TABLE IF EXISTS `cdb_lmt_rename_log`;
CREATE TABLE `cdb_lmt_rename_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL,
  `credit_unit` tinyint(1) NOT NULL DEFAULT '0',
  `credit_number` bigint(20) NOT NULL DEFAULT '0',
  `oldusername` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `newusername` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` bigint(20) NOT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci,
  `ua` varchar(255) COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

EOF;

runquery($sql);

$finish = true;
