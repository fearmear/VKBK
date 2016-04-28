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

if(isset($_GET['auto-queue-photo']) && ($_GET['auto-queue-photo'] == 'true' || $_GET['auto-queue-photo'] == 'false' )){
	$b = ($_GET['auto-queue-photo'] == 'true') ? 1 : 0;
	$q = $db->query("UPDATE vk_status SET `val` = $b WHERE `key` = 'auto-queue-photo'");
	print $b;
}

if(isset($_GET['auto-queue-audio']) && ($_GET['auto-queue-audio'] == 'true' || $_GET['auto-queue-audio'] == 'false' )){
	$b = ($_GET['auto-queue-audio'] == 'true') ? 1 : 0;
	$q = $db->query("UPDATE vk_status SET `val` = $b WHERE `key` = 'auto-queue-audio'");
	print $b;
}

$db->close($res);

?>