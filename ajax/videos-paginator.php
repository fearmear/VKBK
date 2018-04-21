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

$play = $db->query_row("SELECT val as local FROM vk_status WHERE `key` = 'play-local-video'");

$offset_page = ($page > 0) ? $cfg['perpage_video']*$page : 0;
// Get 1 more video to see do we have something on the next page
$perpage = $cfg['perpage_video']+1;
$next = 0;

// Filter Options
$options = '';
$f_type = (isset($_GET['type'])) ? $db->real_escape($_GET['type']) : 'all';
$f_service = (isset($_GET['service'])) ? $db->real_escape($_GET['service']) : 'any';
$f_quality = (isset($_GET['quality'])) ? intval($_GET['quality']) : 0;
$f_length = (isset($_GET['length'])) ? intval($_GET['length']) : 0;
$f_date = (isset($_GET['date']) && $_GET['date'] == 'old') ? 'old' : 'new';
$qsearch = (isset($_GET['qsearch'])) ? $db->real_escape($_GET['qsearch']) : '';

if($f_type == "online"){ $options .= " AND `local_path` = ''"; }
if($f_type == "local"){ $options .= " AND `local_path` != ''"; }

if($f_service == "yt"){ $options .= " AND `player_uri` LIKE '%youtube%'"; }
if($f_service == "vk"){ $options .= " AND `player_uri` LIKE '%vk.com%'"; }

if($f_quality > 0){ $options .= " AND `local_h` = ".$f_quality; }

if($f_length > 0){
	if($f_length == 5){
		$options .= " AND `duration` <= ".$f_length*60;
	} else {
		$options .= " AND `duration` >= ".$f_length*60;
	}
}

if($qsearch != ''){
	$options .= " AND `title` LIKE '%".$qsearch."%'";
}

if($f_date == "new"){ $options .= " ORDER BY `date_added` DESC"; }
if($f_date == "old"){ $options .= " ORDER BY `date_added` ASC"; }

mb_internal_encoding("UTF-8");
$q = $db->query("SELECT * FROM vk_videos WHERE preview_path != '' {$options} LIMIT {$offset_page},{$perpage}");
while($row = $db->return_row($q)){
	if($next < $cfg['perpage_video']){
	// Rewrite if you plan to store content outside of web directory and will call it by Alias
	if($cfg['vhost_alias'] == true && substr($row['preview_path'],0,4) != 'http'){
		$row['preview_path'] = $f->windows_path_alias($row['preview_path'],'video');
	}
	
	// Clean ref
	$row['player_uri'] = preg_replace("/\?__ref\=vk\.api/","",$row['player_uri']);
	
	// Youtube disable fkn Anontation Z
	if(strstr($row['player_uri'],'youtube.com') || strstr($row['player_uri'],'youtu.be')){
		$row['player_uri'] = $row['player_uri'].'?iv_load_policy=3';
	}
	
	$row['stitle'] = $row['title'];
	if(mb_strlen($row['title']) > 38){ $row['stitle'] = mb_substr($row['title'],0,38).'...'; }
	if($row['desc'] != ''){ $row['desc'] = nl2br($row['desc']); }
	$row['duration'] = $skin->seconds2human($row['duration']);
print <<<E
<div class="col-sm-4">
<div class="white-box">
	<div class="video-preview" style="background-image:url('{$row['preview_path']}');">
E;
	if($row['local_path'] != '' && $play['local'] == 1){
print <<<E
		<a class="various-localz" href="javascript:;" data-title-id="title-{$row['id']}" onclick="javascript:fbox_video_global('ajax/local-video.php?id={$row['id']}',1);"><span class="play-icon"><i class="fa fa-play"></i></span></a>
E;
	} else {
print <<<E
		<a class="various-localz" href="javascript:;" data-title-id="title-{$row['id']}" onclick="javascript:fbox_video_global('{$row['player_uri']}',1);"><span class="play-icon"><i class="fa fa-play"></i></span></a>
E;
	}
print <<<E
		<span class="badge bg-light">{$row['duration']}</span>
	</div>
	<div class="video-info">
		<div class="video-title tip" data-placement="top" data-toggle="tooltip" data-original-title="{$row['title']}" onclick="javascript:show_details({$row['id']});"><i class="fa fa-info-circle"></i> | {$row['stitle']}</div>
		<div class="video-status">
E;

	// Show icon for known services
	$service = false;

	// Youtube
	if(strstr($row['player_uri'],'youtube.com') || strstr($row['player_uri'],'youtu.be')){
		$service = true;
		print '<i class="fab fa-youtube" style="color:red;"></i>';
		if($row['local_path'] != ''){
			print ' | Копия: <i class="fa fa-check-square" style="color:#4caf50;"></i> ';
		} else {
			preg_match("/embed\/([^\?]+)\?/",$row['player_uri'],$pu);
			$key = $pu[1];
			print ' | Копия: <i class="fa fa-check-square"></i> <a href="ytget.php?id='.$row['id'].'&key='.$key.'&s=yt" target="_blank">скачать?</a>';
		}
	}
	// Vkontakte
	if(strstr($row['player_uri'],'vk.com')) {
		$service = true;
		print '<i class="fab fa-vk" style="color:#517397;"></i>';
		if($row['local_path'] != ''){
			print ' | Копия: <i class="fa fa-check-square" style="color:#4caf50;"></i> ';
		} else {
			preg_match("/oid\=([\-0-9]+)\&id\=([\-0-9]+)/",$row['player_uri'],$pu);
			$key = $pu[1].'_'.$pu[2];
			print ' | Копия: <i class="fa fa-check-square"></i> <a href="ytget.php?id='.$row['id'].'&key='.$key.'&s=vk" target="_blank">скачать?</a>';
		}
	}
	
	if($service == false){
		print '<i class="fas fa-film"></i>';
	}

print <<<E
		</div>
E;

	if($play['local'] == 0){
print <<<E
		<div id="title-{$row['id']}" style="display:none;">
			{$row['desc']}
			<div class="expander" onClick="expand_desc();">показать</div>
		</div>
E;
	}
	
print <<<E
	</div>
</div></div>
E;
	}
	// Increase NEXT so if we load a full page we would have in the end NEXT = perpage+1
	// Otherwise if next would be lower or equal perpage there is no result for the next page
	$next++;
}

if($next > $cfg['perpage_video']){
	$page++;
	print '<div class="paginator-next"><span class="paginator-val">'.$page.'</span> <a href="/ajax/videos-paginator.php?page='.$page.'&type='.$f_type.'&service='.$f_service.'&quality='.$f_quality.'&length='.$f_length.'&date='.$f_date.'&qsearch='.$qsearch.'">следующая страница</a></div>';
}

$db->close($res);

?>