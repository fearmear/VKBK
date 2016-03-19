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

$offset_page = ($page > 0) ? $cfg['perpage_video']*$page : 0;
// Get 1 more video to see do we have something on the next page
$perpage = $cfg['perpage_video']+1;
$next = 0;
$q = $db->query("SELECT * FROM vk_videos WHERE preview_path != '' ORDER BY `date_added` DESC LIMIT {$offset_page},{$perpage}");
while($row = $db->return_row($q)){
	if($next < $cfg['perpage_video']){
	// Rewrite if you plan to store content outside of web directory and will call it by Alias
	//if(substr($row['preview_path'],0,4) != 'http'){
		//$row['preview_path'] = preg_replace("/^\/VKBK\/video\//","/vkbk-video/",$row['preview_path']);
	//}
	$row['player_uri'] = preg_replace("/\?__ref\=vk\.api/","",$row['player_uri']);
	// Youtube disable fkn Anontation Z
	if(strstr($row['player_uri'],'youtube.com') || strstr($row['player_uri'],'youtu.be')){
		$row['player_uri'] = $row['player_uri'].'?iv_load_policy=3';
	}
	if($row['desc'] != ''){ $row['desc'] = nl2br($row['desc']); }
	$row['duration'] = $skin->seconds2human($row['duration']);
print <<<E
<div class="col-sm-2">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h3 class="panel-title"><i class="fa fa-film"></i> {$row['title']}</h3>
            </div>
            <div class="panel-body">
			  <span class="label label-primary">{$row['duration']}</span>
              <a class="various fancybox.iframe" href="{$row['player_uri']}" data-title-id="title-{$row['id']}"><img src="{$row['preview_path']}" /></a>
			  <div id="title-{$row['id']}" style="display:none;">
				{$row['desc']}
				<div class="expander" onClick="expand_desc();">показать</div>
			  </div>
            </div>
          </div>
</div>
E;
	}
	// Increase NEXT so if we load a full page we would have in the end NEXT = perpage+1
	// Otherwise if next would be lower or equal perpage there is no result for the next page
	$next++;
}

if($next > $cfg['perpage_video']){
	$page++;
	print '<div class="paginator-next" style="display:none;"><a href="ajax/videos-paginator.php?page='.$page.'">следующая страница</a></div>';
}

$db->close($res);

?>