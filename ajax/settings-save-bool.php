<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('../cfg.php');

// Get DB
require_once(ROOT.'classes/db.php');
$db = new db();
$res = $db->connect($cfg['host'],$cfg['user'],$cfg['pass'],$cfg['base']);

$bool_opions = array(
	'auto-queue-photo',
	'auto-queue-audio',
	'play-local-video',
	'start-local-video'
);

if(isset($_GET['option']) && isset($_GET['v']) && in_array($_GET['option'],$bool_opions) && ($_GET['v'] == 'true' || $_GET['v'] == 'false')){
	$b = ($_GET['v'] == 'true') ? 1 : 0;
	$q = $db->query_row("SELECT `key` FROM vk_status WHERE `key` = '".$db->real_escape($_GET['option'])."'");
	if($q['key'] != ''){
		$q = $db->query("UPDATE vk_status SET `val` = $b WHERE `key` = '".$db->real_escape($_GET['option'])."'");
	} else {
		$q = $db->query("INSERT INTO vk_status (`key`,`val`) VALUES ('".$db->real_escape($_GET['option'])."','".$b."')");
	}
	print $b;
} else {
	print 'Неопознанный параметр';
}

$db->close($res);

?>