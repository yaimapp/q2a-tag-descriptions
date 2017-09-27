<?php

if (!defined('QA_VERSION')) {
	require_once dirname(empty($_SERVER['SCRIPT_FILENAME']) ? __FILE__ : $_SERVER['SCRIPT_FILENAME']).'/../../qa-include/qa-base.php';
}

error_log('-----------------------------------');
error_log('script start');
require_once QA_PLUGIN_DIR.'q2a-tag-descriptions/similar-tag-db.php';
$start = microtime(true);
if (qa_using_tags()) {
	$stdb = new desc_similar_tag_db();;
	$cnt = $stdb->update_all_similar_gats();
	error_log('処理件数: ' . $cnt);
}
$end = microtime(true);
error_log("処理時間：" . ($end - $start) . "秒");
error_log('script finished');
error_log('-----------------------------------');

/*
	Omit PHP closing tag to help avoid accidental output
*/
