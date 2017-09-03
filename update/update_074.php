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

// Get Skin
require_once(ROOT.'classes/skin.php');
$skin = new skin();

// Get Functions
require_once(ROOT.'classes/func.php');
$f = new func();

$ex_top = <<<E
<style type="text/css">
body {padding-top:10px;margin-bottom:10px;}
.wall-box{margin-bottom:0;}
</style>
E;

print $skin->header(array('extend'=>$ex_top));

print <<<E
<div class="wall-body">
    <div class="container" id="wall-list">
E;

$found = $db->query_row("SELECT COUNT(*) as total FROM `vk_wall` WHERE `repost` != 0 AND repost_owner = 0 AND is_repost = 0");

$need_db = '2017090201';
$current_db = ($need_db == $cfg['version_db']) ? true : false;

print <<<E
<div class="row">
    <div class="col-sm-12 wall-box">
		<ul class="list-group list-unstyled">
			<div>Скрипт обновления удаленных репостов для версии <b>0.7.4</b></div>
			<li><label><span class="label label-info">информация</span></label><p>Ваша версия VKBK: <b>{$cfg['version']}</b></p></li>
		</ul>
	
		<ul class="list-group list-unstyled">
			<div>База данных</div>
E;
if($current_db === true){
	print '<li><label><span class="label label-success">порядок</span></label><p>Правильная версия базы данных: <b>'.$cfg['version_db'].'</b></p></li>';
} else {
	print '<li><label><span class="label label-danger">ошибка</span></label><p>Версия базы данных отличается от необходимой!</br>Ваша версия: <b>'.$cfg['version_db'].'</b></br>Требуемая версия: <b>'.$need_db.'</b></p></li>';
}
print <<<E
		</ul>
		<ul class="list-group list-unstyled">
			<div>Данные</div>
			<li><label><span class="label label-info">информация</span></label><p>Найдено <b>{$found['total']}</b> записей требующих обновновления.</br>Если найдено 0 записей, обновление не требуется.</p></li>
		</ul>
E;

if(!isset($_GET['update']) && $found['total'] > 0){
print <<<E
		<hr/>
		<div style="text-align:center;">
			<a href="update/update_074.php?update=true" class="btn btn-success">Обновить</a>
		</div>
E;
}

if(isset($_GET['update']) && $_GET['update'] == true && $found['total'] > 0){
	$update_ids = array();
// Get rows for update
$r = $db->query("SELECT * FROM `vk_wall` WHERE `repost` != 0 AND repost_owner = 0 AND is_repost = 0");
while($row = $db->return_row($r)){
	$owner = $db->query_row("SELECT owner_id FROM `vk_wall` WHERE `id` = {$row['repost']}");
	$update_ids[$row['id']] = $owner['owner_id'];
}

// Update
foreach($update_ids as $id => $repost){
	$db->query("UPDATE `vk_wall` SET `repost_owner` = {$repost} WHERE `id` = {$id}");
}
	print '<hr/>Данные успешно обновлены.';
}

print <<<E
	</div>
</div>

          </div>
</div>
</body>
</html>
E;

$db->close($res);

?>