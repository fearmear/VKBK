<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check do we have all needed GET data
$page = 0;
if(isset($_GET['page']) && is_numeric($_GET['page'])){
	$p = intval($_GET['page']);
	if($p > 0){ $page = $p; }
}

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

$offset_page = ($page > 0) ? $cfg['perpage_docs']*$page : 0;
// Get 1 more video to see do we have something on the next page
$perpage = $cfg['perpage_docs']+1;
$next = 0;

// Filter Options
$options = '';
$f_type = (isset($_GET['type'])) ? mysql_real_escape_string($_GET['type']) : 'all';

if($f_type == "1"){ $options .= " AND `type` = 1"; }
if($f_type == "2"){ $options .= " AND `type` = 2"; }
if($f_type == "3"){ $options .= " AND `type` = 3"; }
if($f_type == "4"){ $options .= " AND `type` = 4"; }
if($f_type == "5"){ $options .= " AND `type` = 5"; }
if($f_type == "6"){ $options .= " AND `type` = 6"; }
if($f_type == "7"){ $options .= " AND `type` = 7"; }
if($f_type == "8"){ $options .= " AND `type` = 8"; }

mb_internal_encoding("UTF-8");
$q = $db->query("SELECT * FROM vk_docs WHERE local_path != '' {$options} ORDER BY `date` DESC LIMIT {$offset_page},{$perpage}");
while($row = $db->return_row($q)){
	if($next < $cfg['perpage_docs']){
	// Rewrite if you plan to store content outside of web directory and will call it by Alias
	if($cfg['vhost_alias'] == true && substr($row['local_path'],0,4) != 'http'){
		$row['local_path'] = $f->windows_path_alias($row['local_path'],'docs');
	}
	if($cfg['vhost_alias'] == true && substr($row['preview_path'],0,4) != 'http' && $row['preview_path'] != ''){
		$row['preview_path'] = $f->windows_path_alias($row['preview_path'],'docs');
	}
	
	$row['stitle'] = $row['title'];
	if(mb_strlen($row['title']) > 40){ $row['stitle'] = mb_substr($row['title'],0,40).'...'; }
print <<<E
<div class="col-sm-4">
<div class="white-box">
	
E;
	if($row['preview_path'] != ''){
		if($row['type'] == 3){
print <<<E
	<div class="docs-preview docs-gif" style="background-image:url('{$row['preview_path']}');" data-src-local="{$row['local_path']}" data-pre-local="{$row['preview_path']}">
E;
		} else {
print <<<E
	<div class="docs-preview" style="background-image:url('{$row['preview_path']}');">
E;
		}
print <<<E
		<a class="various-local fancybox" href="{$row['local_path']}" data-title="{$row['title']}"></a>
		<span class="label">{$row['ext']}</span>
	</div>
E;
	} else {
print <<<E
	<div class="docs-preview">
		<a href="{$row['local_path']}" target="_blank"><span class="docs-icon"><i class="fa fa-file"></i></span></a>
		<span class="label">{$row['ext']}</span>
	</div>
E;
	}
print <<<E
	<div class="docs-info">
		<div class="docs-title tip" data-placement="top" data-toggle="tooltip" data-original-title="{$row['title']}">{$row['stitle']}</div>
	</div>
</div></div>
E;
	}
	// Increase NEXT so if we load a full page we would have in the end NEXT = perpage+1
	// Otherwise if next would be lower or equal perpage there is no result for the next page
	$next++;
}

if($next > $cfg['perpage_docs']){
	$page++;
	print '<div class="paginator-next"><span class="paginator-val">'.$page.'</span><a href="/ajax/docs-paginator.php?page='.$page.'&type='.$f_type.'">следующая страница</a></div>';
}

$db->close($res);

?>