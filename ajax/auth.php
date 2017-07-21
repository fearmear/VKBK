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

$opions = array(
	'logout'
);
$output = '';

if(isset($_GET['do']) && in_array($_GET['do'],$opions)){
	if($_GET['do'] == 'logout'){
		$q = $db->query("UPDATE vk_session SET `vk_token` = '' WHERE `vk_id` = 1");
		$output = '<p>Вы вышли</p><i class="fa fa-power-off" style="font-size:3em;color:#eee;"></i>';
	}
} else {
	$output = '<p>Неопознанный параметр</p><i class="fa fa-times" style="font-size:3em;color:#d9534f;"></i>';
}

print <<<E
<li style="text-align:center;padding:20px 0;">{$output}</li>
E;

$db->close($res);

?>