<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_lmt_rename extends discuz_table
{
	public function __construct() {

		$this->_table = 'lmt_rename_log';
		$this->_pk    = '';

		parent::__construct();
	}

	public function delete_by_id($id) {
		DB::query("DELETE FROM %t WHERE id=%d", array($this->_table, $id));
	}

	public function count_by_search($condition) {
		return DB::result_first("SELECT count(id) FROM %t WHERE 1 %i", [$this->_table, $condition]);
	}

	public function fetch_all_by_search($condition, $start, $ppp) {
		return DB::fetch_all("SELECT * FROM %t WHERE 1 %i ORDER BY dateline DESC LIMIT %d, %d", array($this->_table, $condition, $start, $ppp));
	}
}
