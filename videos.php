<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('./cfg.php');

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

$row = $db->query_row("SELECT val as version FROM vk_status WHERE `key` = 'version'");
$version = $row['version'];

// Get local counters for top menu
$lc = $db->query_row("SELECT * FROM vk_counters");

$ex_top = <<<E
<link rel="stylesheet" href="/css/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
<link rel="stylesheet" href="/css/jquery.fancybox-buttons.css?v=1.0.5" type="text/css" media="screen" />
E;

print $skin->header(array('extend'=>$ex_top));
print $skin->navigation($lc);

print <<<E
<div class="container">
          <h2 class="sub-header"><i class="fa fa-film"></i> Видео</h2>
          <div class="container" id="video-list">
E;

	$play = $db->query_row("SELECT val as local FROM vk_status WHERE `key` = 'play-local-video'");

	$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 0;
	$npage = $page+1;
	$offset_page = ($page > 0) ? $cfg['perpage_video']*$page : 0;

	mb_internal_encoding("UTF-8");
$r = $db->query("SELECT * FROM vk_videos WHERE preview_path != '' ORDER BY date_added DESC LIMIT {$offset_page},{$cfg['perpage_video']}");
while($row = $db->return_row($r)){
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
	if(mb_strlen($row['title']) > 40){ $row['stitle'] = mb_substr($row['title'],0,40).'...'; }
	if($row['desc'] != ''){ $row['desc'] = nl2br($row['desc']); }
	$row['duration'] = $skin->seconds2human($row['duration']);
print <<<E
<div class="col-sm-4">
<div class="white-box">
	<div class="video-preview" style="background-image:url('{$row['preview_path']}');">
E;
	if($row['local_path'] != '' && $play['local'] == 1){
print <<<E
		<a class="various-local fancybox.iframe" href="{$cfg['vkbk_url']}ajax/local-video.php?id={$row['id']}" data-title-id="title-{$row['id']}"><span class="play-icon"><i class="fa fa-play"></i></span></a>
E;
	} else {
print <<<E
		<a class="various fancybox.iframe" href="{$row['player_uri']}" data-title-id="title-{$row['id']}"><span class="play-icon"><i class="fa fa-play"></i></span></a>
E;
	}
print <<<E
		<span class="label">{$row['duration']}</span>
	</div>
	<div class="video-info">
		<div class="video-title tip" data-placement="top" data-toggle="tooltip" data-original-title="{$row['title']}">{$row['stitle']}</div>
		<div class="video-status">
		
E;

	// Show icon for known services
	$service = false;

	// Youtube
	if(strstr($row['player_uri'],'youtube.com') || strstr($row['player_uri'],'youtu.be')){
		$service = true;
		print 'Источник: <i class="fa fa-youtube" style="color:red;"></i>';
		if($row['local_path'] != ''){
			print ' | Копия: <b style="color:#33567f">есть</b>; '.strtoupper($row['local_format']).' '.$row['local_w'].'x'.$row['local_h'].' '.$f->human_filesize($row['local_size']);
		} else {
			preg_match("/embed\/([^\?]+)\?/",$row['player_uri'],$pu);
			$key = $pu[1];
			print ' | Копия: <b>нет</b> <a href="/ytget.php?id='.$row['id'].'&key='.$key.'&s=yt" target="_blank">скачать?</a>';
		}
	}
	// Vkontakte
	if(strstr($row['player_uri'],'vk.com')) {
		$service = true;
		print 'Источник: <i class="fa fa-vk" style="color:#517397;"></i>';
		if($row['local_path'] != ''){
			print ' | Копия: <b style="color:#33567f">есть</b>; '.strtoupper($row['local_format']).' '.$row['local_h'].'p '.$f->human_filesize($row['local_size']);
		} else {
			preg_match("/oid\=([\-0-9]+)\&id\=([\-0-9]+)/",$row['player_uri'],$pu);
			$key = $pu[1].'_'.$pu[2];
			print ' | Копия: <b>нет</b> <a href="/ytget.php?id='.$row['id'].'&key='.$key.'&s=vk" target="_blank">скачать?</a>';
		}
	}
	
	if($service == false){
		print 'Источник: <i class="fa fa-film"></i>';
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

print <<<E
			<div class="paginator-next" style="display:none;"><span class="paginator-val">{$npage}</span><a href="/ajax/videos-paginator.php?page={$npage}">следующая страница</a></div>
          </div>
</div>
E;

// Fancybox Options
$fancybox_options = <<<E
		fitToView	: false,
		autoSize	: false,
		closeClick	: false,
		openEffect	: 'none',
		closeEffect	: 'none',
		
		padding : 5,
		arrows : false,
		closeBtn : false,
		nextClick : false,
		loop : false,
	    helpers : {
	       overlay : {
	           css : {
	               'background' : 'rgba(0, 0, 0, 0.85)'
	            }
	       },
		   title: {
				type: 'inside'
	       }
	    },
		beforeLoad: function() {
            var el, id = $(this.element).data('title-id');
	
            if (id) {
                el = $('#' + id);
            
                if (el.length) {
                    this.title = el.html();
                }
            }
        }
E;

$ex_bot = <<<E
<script type="text/javascript" src="/js/jquery.jscroll.min.js"></script>
<script type="text/javascript" src="/js/jquery.fancybox.pack.js?v=2.1.5"></script>
<script type="text/javascript" src="/js/jquery.fancybox-buttons.js?v=1.0.5"></script>
<script type="text/javascript" src="/js/hashnav.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	var notload = false;
	var list = jQuery("#video-list");

	// Hash URL commands
	urlCommands.bind('page', function(id) {
		if($.isNumeric(id) && id >= 2){
			notload = true;
			for(i=2;i<=id;i++){
				jQuery.ajax({
					async : false,
					method : "GET",
					url : "http://bkvk.local/ajax/videos-paginator.php?page="+i+""
				}).done( function(data){
					jQuery(".paginator-next").remove();
					list.append(data);
				});
			}
			notload = false;
		}
	});

	if(notload == false){
$('#video-list').jscroll({
	debug:true,
    nextSelector: 'div.paginator-next > a:last',
	padding: 20,
	callback: function(){
		$(".tip").tooltip();
		var pval = jQuery("div.paginator-next:last .paginator-val").html();
		if($.isNumeric(pval)){ urlCommands.urlPush({page:pval}); }
	}
});
	}

	$(".various").fancybox({
		maxWidth	: 1280,
		maxHeight	: 720,
		width		: '70%',
		height		: '70%',
		{$fancybox_options}
	});
	
	$(".various-local").fancybox({
		maxWidth	: 1340,
		maxHeight	: 820,
		width		: '95%',
		height		: '95%',
		{$fancybox_options}
	});
	
	$(".tip").tooltip();
});

	function expand_desc(){
		var el = jQuery(".fancybox-title-inside-wrap");
		if(el.css("height") == '40px'){
			el.css("height","auto");
			jQuery(".fancybox-title-inside-wrap > .expander").html("свернуть");
		} else {
			el.css("height","40px");
			jQuery(".fancybox-title-inside-wrap > .expander").html("показать");
		}
	}
</script>
E;

print $skin->footer(array(
	'v'=>$version,
	'extend'=> $ex_bot,
));

$db->close($res);

?>