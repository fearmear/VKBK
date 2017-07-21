<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check do we have all needed GET data
$album_id = false;
if(isset($_GET['id']) && is_numeric($_GET['id'])){
	$album_id = intval($_GET['id']);
}
$page = 0;
if(isset($_GET['page']) && is_numeric($_GET['page'])){
	$p = intval($_GET['page']);
	if($p > 0){ $page = $p; }
}

if($album_id == false){
	die();
}

require_once('../cfg.php');

// Get Functions
require_once(ROOT.'classes/func.php');
$f = new func();

// Get DB
require_once(ROOT.'classes/db.php');
$db = new db();
$res = $db->connect($cfg['host'],$cfg['user'],$cfg['pass'],$cfg['base']);

$offset_page = ($page > 0) ? $cfg['perpage_photo']*$page : 0;
// Get 1 more photo to see do we have something on the next page
$perpage = $cfg['perpage_photo']+1;
$next = 0;
$q = $db->query("SELECT * FROM vk_photos WHERE `saved` = 1 AND `album_id` = {$album_id} ORDER BY `date_added` DESC LIMIT {$offset_page},{$perpage}");
while($row = $db->return_row($q)){
	if($next < $cfg['perpage_photo']){
	// Rewrite if you plan to store content outside of web directory and will call it by Alias
	if($cfg['vhost_alias'] == true && substr($row['path'],0,4) != 'http'){
		$row['path'] = $f->windows_path_alias($row['path'],'photo');
	}
print <<<E
    <div class="brick" style='width:{$cfg['photo_layout_width']}px;'><a class="fancybox" rel="album" href="{$row['path']}" data-fancybox="images"><img style="width:100%" src="{$row['path']}"></a></div>
E;
	}
	// Increase NEXT so if we load a full page we would have in the end NEXT = perpage+1
	// Otherwise if next would be lower or equal perpage there is no result for the next page
	$next++;
}

if($next > $cfg['perpage_photo']){
	$page++;
	print '<div class="paginator-next" style="display:none;"><a href="ajax/albums-paginator.php?id='.$album_id.'&page='.$page.'">следующая страница</a></div>';
}

$db->close($res);

?>