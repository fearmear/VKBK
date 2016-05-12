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
<link rel="stylesheet" href="/css/perfect-scrollbar.min.css?v=0.6.11" type="text/css" media="screen" />
E;

print $skin->header(array('extend'=>$ex_top));
print $skin->navigation($lc);

$album_id = (isset($_GET['id'])) ? intval($_GET['id']) : '';
$header = '';

print <<<E
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-3 col-md-2 sidebar" style="overflow-y:auto;padding:0 20px 10px 10px;">
          <ul class="nav nav-sidebar">
		  <li class="albums-list">
E;

$r = $db->query("SELECT * FROM vk_albums ORDER BY id ASC");
while($album_list = $db->return_row($r)){
	$full_name = $album_list['name'];
	mb_internal_encoding("UTF-8");
	if(mb_strlen($album_list['name']) > 10){ $album_list['name'] = mb_substr($album_list['name'],0,12).'<small>...</small>'; }
	
	if($album_list['id'] == $album_id){
		print '<a class="full-name" data-placement="top" data-toggle="tooltip" data-original-title="'.$full_name.'" href="albums.php?id='.$album_list['id'].'"><i class="fa fa-folder-open"></i>&nbsp;&nbsp;'.$album_list['name'].'&nbsp;<span>'.$album_list['img_done'].'</span></a>';
	} else {
		print '<a class="full-name" data-placement="top" data-toggle="tooltip" data-original-title="'.$full_name.'" href="albums.php?id='.$album_list['id'].'"><i class="fa fa-folder" style="color:#777;"></i>&nbsp;&nbsp;'.$album_list['name'].'&nbsp;<span>'.$album_list['img_done'].'</span></a>';
	}
}

print <<<E
</li>
          </ul>
        </div>
E;

$photos = '';
$pic_albums = '';

if($album_id){
	$album = $db->query_row("SELECT * FROM vk_albums WHERE `id` = {$album_id}");
	$header = '<i class="fa fa-folder-open-o"></i> '.$album['name'].($album['img_total'] > $album['img_done'] ? ' <a href="sync.php?do=album&id='.$album_id.'" class="btn btn-primary btn-lg" role="button">Синхр.</a>' : '');
		
	$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 0;
	$npage = $page+1;
	$offset_page = ($page > 0) ? $cfg['perpage_photo']*$page : 0;
	$q = $db->query("SELECT * FROM vk_photos WHERE `saved` = 1 AND `album_id` = {$album_id} ORDER BY `date_added` DESC LIMIT {$offset_page},{$cfg['perpage_photo']}");
	while($row = $db->return_row($q)){
		// Rewrite if you plan to store content outside of web directory and will call it by Alias
		if($cfg['vhost_alias'] == true && substr($row['path'],0,4) != 'http'){
			$row['path'] = $f->windows_path_alias($row['path'],'photo');
		}
$photos .= <<<E
    <div class="brick" style='width:{$cfg['photo_layout_width']}px;'><a class="fancybox" rel="album" href="{$row['path']}"><img style="width:100%" src="{$row['path']}"></a></div>
E;
	}
	
} else { // end if album id

	$q = $db->query("SELECT a.*, p.path FROM vk_albums a LEFT JOIN vk_photos p ON a.id = p.album_id RIGHT JOIN ( SELECT album_id, MAX( date_added ) AS mdate FROM vk_photos GROUP BY album_id ) p2 ON a.id = p2.album_id WHERE p.date_added = mdate GROUP BY a.id");
	while($arow = $db->return_row($q)){
		if($cfg['vhost_alias'] == true && substr($arow['path'],0,4) != 'http'){
			$arow['path'] = $f->windows_path_alias($arow['path'],'photo');
		}
		if(mb_strlen($arow['name']) > 10){ $arow['name'] = mb_substr($arow['name'],0,12).'<small>...</small>'; }
$pic_albums .= <<<E
<div class="col-sm-3">
<a href="albums.php?id={$arow['id']}" style="background-image:url('{$arow['path']}');"><span>{$arow['name']}</span></a>
</div>
E;
	}

	// Show latest photos
	$header = '<i class="fa fa-image"></i> Последние фотографии';
	$q = $db->query("SELECT * FROM vk_photos WHERE `saved` = 1 ORDER BY `date_done` DESC LIMIT 0,25");
	while($row = $db->return_row($q)){
		// Rewrite if you plan to store content outside of web directory and will call it by Alias
		if($cfg['vhost_alias'] == true && substr($row['path'],0,4) != 'http'){
			$row['path'] = $f->windows_path_alias($row['path'],'photo');
		}
$photos .= <<<E
    <div class="brick" style='width:{$cfg['photo_layout_width']}px;'><a class="fancybox" rel="album" href="{$row['path']}"><img style="width:100%" src="{$row['path']}"></a></div>
E;
	}
	$npage = 1;

}

print <<<E
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
		  
E;
		if(!$album_id){
			print '<div class="row pic-albums">'.$pic_albums.'</div>';
		}
print <<<E
		  <h1 class="page-header">{$header}</h1>
          <div class="free-wall" id="freewall">
			{$photos}
			<div class="paginator-next" style="display:none;"><a href="ajax/albums-paginator.php?id={$album_id}&page={$npage}">следующая страница</a></div>
          </div>
    </div>
E;

$ex_bot = <<<E
<script type="text/javascript" src="/js/freewall.js"></script>
<script type="text/javascript" src="/js/jquery.jscroll.min.js"></script>
<script type="text/javascript" src="/js/jquery.fancybox.pack.js?v=2.1.5"></script>
<script type="text/javascript" src="/js/jquery.fancybox-buttons.js?v=1.0.5"></script>
<script type="text/javascript" src="/js/perfect-scrollbar.jquery.min.js?v=0.6.11"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('.sidebar').perfectScrollbar();

	var wall = new Freewall("#freewall");
	wall.reset({
		selector: '.brick',
		animate: false,
		cellW: {$cfg['photo_layout_width']},
		cellH: 'auto',
		keepOrder: true,
		onResize: function() {
			wall.fitWidth();
		}
	});
	
	//var images = wall.container.find('.brick');
	//images.find('img').load(function() {
	wall.fitWidth();
	//});
	
$('.free-wall').jscroll({
	debug:false,
    nextSelector: 'div.paginator-next > a:last',
	padding: 200,
	callback: function(){
		$(".jscroll-added:last").each(function(){
			wall.fitWidth();
			$(window).trigger("resize");
		});
	}
});

	$(".fancybox").fancybox({
		padding : 5,
		arrows : false,
		closeBtn : false,
		nextClick : true,
		loop : false,
		keys : {
			toggle : [32], // space - toggle fullscreen
			play : [70]
		},
	    helpers : {
	       overlay : {
	           css : {
	               'background' : 'rgba(0, 0, 0, 0.85)'
	            }
	       },
		   buttons : {}
	    }

	});
	
	$(".full-name").tooltip();
	
	picalbschk();
	
});

$(window).resize(function() {
	picalbschk();
});

function pic_albs(){
	$(".pic-albums").css({"overflow":"visible","height":"auto"});
	$(".pic-albums-more").hide();
}
function picalbschk(){
	var picalbs = $(".pic-albums .col-sm-3:nth-child(4)");
	if($(window).width() <= 768){
		var picalbs = $(".pic-albums .col-sm-3:nth-child(1)");
	}
	$(".pic-albums-more").remove();
	picalbs.after("<div class=\"pic-albums-more\" onclick=\"javascript:pic_albs();\">показать все альбомы</div>");
}
</script>

E;
print $skin->footer(array(
	'v'=>$version,
	'extend'=> $ex_bot,
));

$db->close($res);

?>