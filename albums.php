<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('./cfg.php');
if(isset($_GET['_pjax']) || isset($_POST['_pjax'])){ $cfg['pj'] = true; }

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

// Get local counters for top menu
$lc = $db->query_row("SELECT * FROM vk_counters");

if(!$cfg['pj']){
	print $skin->header(array('extend'=>''));
	print $skin->navigation($lc);
}

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
		print '<a class="full-name" data-placement="top" data-toggle="tooltip" data-original-title="'.$full_name.'" href="albums.php?id='.$album_list['id'].'" data-pjax><i class="fa fa-folder-open"></i>&nbsp;&nbsp;'.$album_list['name'].'&nbsp;<span>'.$album_list['img_done'].'</span></a>';
	} else {
		print '<a class="full-name" data-placement="top" data-toggle="tooltip" data-original-title="'.$full_name.'" href="albums.php?id='.$album_list['id'].'" data-pjax><i class="fa fa-folder" style="color:#777;"></i>&nbsp;&nbsp;'.$album_list['name'].'&nbsp;<span>'.$album_list['img_done'].'</span></a>';
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
    <div class="brick" style='width:{$cfg['photo_layout_width']}px;'><a class="fancybox" rel="album" href="{$row['path']}" data-fancybox="images"><img style="width:100%" src="{$row['path']}"></a></div>
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
<a href="albums.php?id={$arow['id']}" data-pjax style="background-image:url('{$arow['path']}');"><span>{$arow['name']}</span></a>
</div>
E;
	}

	// Show latest photos
	$header = '<i class="fa fa-image"></i> Последние фотографии';
	$q = $db->query("SELECT * FROM vk_photos WHERE `saved` = 1 ORDER BY `date_added` DESC LIMIT 0,25");
	while($row = $db->return_row($q)){
		// Rewrite if you plan to store content outside of web directory and will call it by Alias
		if($cfg['vhost_alias'] == true && substr($row['path'],0,4) != 'http'){
			$row['path'] = $f->windows_path_alias($row['path'],'photo');
		}
$photos .= <<<E
    <div class="brick" style='width:{$cfg['photo_layout_width']}px;'><a class="fancybox" rel="album" href="{$row['path']}" data-fancybox="images"><img style="width:100%" src="{$row['path']}"></a></div>
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

// Fancybox Options
$fancybox_options = <<<E
	loop		: true,
	keyboard	: true,
	arrows		: true,
	infobar		: false,
	toolbar		: true,
	buttons		: [ 'fullScreen','close' ],
	animationEffect		: false,
	transitionEffect	: false,
	touch		: {
		vertical	: false
	},
	hash		: false
E;

$ex_bot = <<<E
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
		$(".fancybox").fancybox({ {$fancybox_options} });
		$(".jscroll-added").last().delay(500).trigger("resize");
	}
});

	$(".fancybox").fancybox({
		{$fancybox_options}
	});
	
	$(".full-name").tooltip();
	
	picalbschk();
	
});

$(window).resize(function() {
	picalbschk();
});

/* Toggle Visibility of Albums List */
function pic_albs(){
	$(".pic-albums").css({"overflow":"visible","height":"auto"});
	$(".pic-albums-more").hide();
}

/* Check and Place `show all alums` button */
function picalbschk(){
	var ww = $(window).width();
	if(jQuery(".pic-albums").css("overflow") === "hidden"){
		$(".pic-albums-more").remove();
		var picalbs = $(".pic-albums .col-sm-3:nth-child(4)");
		if(ww <= 768){
			var picalbs = $(".pic-albums .col-sm-3:nth-child(1)");
		}
		picalbs.after("<div class=\"pic-albums-more\" onclick=\"javascript:pic_albs();\">показать все альбомы</div>");
	}
}
</script>

E;

if(!$cfg['pj']){
	print $skin->footer(array('extend'=> $ex_bot));
} else {
	print $ex_bot;
}

$db->close($res);

?>