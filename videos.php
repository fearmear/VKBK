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

$row = $db->query_row("SELECT val as version FROM vk_status WHERE `key` = 'version'");
$version = $row['version'];

// Get local counters for top menu
$lc = $db->query_row("SELECT * FROM vk_counters");

$ex_top = <<<E
<link rel="stylesheet" href="css/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
<link rel="stylesheet" href="css/jquery.fancybox-buttons.css?v=1.0.5" type="text/css" media="screen" />
E;

print $skin->header(array('extend'=>$ex_top));
print $skin->navigation($lc);

print <<<E
<div class="container">
          <h2 class="sub-header"><i class="fa fa-film"></i> Видео</h2>
          <div class="container" id="video-list">
E;


	$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 0;
	$npage = $page+1;
	$offset_page = ($page > 0) ? $cfg['perpage_video']*$page : 0;

$r = $db->query("SELECT * FROM vk_videos WHERE preview_path != '' ORDER BY date_added DESC LIMIT {$offset_page},{$cfg['perpage_video']}");
while($row = $db->return_row($r)){
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

print <<<E
			<div class="paginator-next" style="display:none;"><a href="ajax/videos-paginator.php?page={$npage}">следующая страница</a></div>
          </div>
</div>
E;

$ex_bot = <<<E
<script type="text/javascript" src="js/jquery.jscroll.min.js"></script>
<script type="text/javascript" src="js/jquery.fancybox.pack.js?v=2.1.5"></script>
<script type="text/javascript" src="js/jquery.fancybox-buttons.js?v=1.0.5"></script>
<script type="text/javascript">

$(document).ready(function() {

$('#video-list').jscroll({
	debug:false,
    nextSelector: 'div.paginator-next > a:last',
	padding: 20
});

	$(".various").fancybox({
		maxWidth	: 1280,
		maxHeight	: 720,
		fitToView	: false,
		width		: '70%',
		height		: '70%',
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

	});
		
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