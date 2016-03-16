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

$album_id = (isset($_GET['id'])) ? intval($_GET['id']) : '';
$header = '';

print <<<E
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
          <ul class="nav nav-sidebar">
E;

$r = $db->query("SELECT * FROM vk_albums ORDER BY id ASC");
while($album_list = $db->return_row($r)){
	if($album_list['id'] == $album_id){
		print '<li class="active"><a href="albums.php?id='.$album_list['id'].'"><span class="label label-default">'.$album_list['img_done'].'</span> '.$album_list['name'].'<span class="sr-only">(current)</span></a></li>';
	} else {
		print '<li><a href="albums.php?id='.$album_list['id'].'"><span class="label label-primary">'.$album_list['img_done'].'</span> '.$album_list['name'].'</a></li>';
	}
}

print <<<E
          </ul>
        </div>
E;

if($album_id){
	$album = $db->query_row("SELECT * FROM vk_albums WHERE `id` = {$album_id}");
	$header = '<i class="fa fa-folder-open-o"></i> '.$album['name'].($album['img_total'] > $album['img_done'] ? ' <a href="sync.php?do=album&id='.$album_id.'" class="btn btn-primary btn-lg" role="button">Синхр.</a>' : '');
		
	$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 0;
	$npage = $page+1;
	$offset_page = ($page > 0) ? $cfg['perpage_photo']*$page : 0;
	$q = $db->query("SELECT * FROM vk_photos WHERE `saved` = 1 AND `album_id` = {$album_id} ORDER BY `date_added` DESC LIMIT {$offset_page},{$cfg['perpage_photo']}");
	while($row = $db->return_row($q)){
		// Rewrite if you plan to store content outside of web directory and will call it by Alias
		//if(substr($row['path'],0,4) != 'http'){
			//$row['path'] = preg_replace("/^\/VKBK\/photo\//","/vkbk-photo/",$row['path']);
		//}
$photos .= <<<E
    <div class="brick" style='width:{$cfg['photo_layout_width']}px;'><a class="fancybox" rel="album" href="{$row['path']}"><img style="width:100%" src="{$row['path']}"></a></div>
E;
	}
	
} else { // end if album id

	// Show latest photos
	$header = '<i class="fa fa-image"></i> Последние фотографии';
	$q = $db->query("SELECT * FROM vk_photos WHERE `saved` = 1 ORDER BY `date_done` DESC LIMIT 0,25");
	while($row = $db->return_row($q)){
		// Rewrite if you plan to store content outside of web directory and will call it by Alias
		//if(substr($row['path'],0,4) != 'http'){
			//$row['path'] = preg_replace("/^\/VKBK\/photo\//","/vkbk-photo/",$row['path']);
		//}
$photos .= <<<E
    <div class="brick" style='width:{$cfg['photo_layout_width']}px;'><a class="fancybox" rel="album" href="{$row['path']}"><img style="width:100%" src="{$row['path']}"></a></div>
E;
	}

}

print <<<E
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <h1 class="page-header">{$header}</h1>
		  <p>{$desc}</p>
          <div class="free-wall" id="freewall">
			{$photos}
			<div class="paginator-next" style="display:none;"><a href="ajax/albums-paginator.php?id={$album_id}&page={$npage}">следующая страница</a></div>
          </div>
    </div>

E;

$ex_bot = <<<E
<script type="text/javascript" src="js/freewall.js"></script>
<script type="text/javascript" src="js/jquery.jscroll.min.js"></script>
<script type="text/javascript" src="js/jquery.fancybox.pack.js?v=2.1.5"></script>
<script type="text/javascript" src="js/jquery.fancybox-buttons.js?v=1.0.5"></script>
<script type="text/javascript">

$(document).ready(function() {
	var wall = new Freewall("#freewall");
	wall.reset({
		selector: '.brick',
		animate: true,
		cellW: {$cfg['photo_layout_width']},
		cellH: 'auto',
		onResize: function() {
			wall.fitWidth();
		}
	});
	
	var images = wall.container.find('.brick');
	images.find('img').load(function() {
	wall.fitWidth();
	});
	
$('.free-wall').jscroll({
	debug:false,
    nextSelector: 'div.paginator-next > a:last',
	padding: 200,
	callback: function(){
		wall.refresh();
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
	
});
</script>

E;
print $skin->footer(array(
	'v'=>$version,
	'extend'=> $ex_bot,
));

$db->close($res);

?>