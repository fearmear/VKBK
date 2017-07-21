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

print <<<E
<div class="container" style="position:relative;">

<button type="button" class="btn btn-default docs-filter-btn"><i class="fa fa-filter"></i></button>
<div class="col-sm-4 white-box docs-filter-box">
	<h4><i class="fa fa-filter"></i> Фильтр</h4>
	<div class="row">
	<label for="type">Тип</label>
	<select class="selectpicker show-tick" name="type" id="f-type">
		<option data-icon="fa-globe" value="all">Любой</option>
		<option data-icon="fa-file-text-o" value="1">Текстовые документы</option>
		<option data-icon="fa-file-archive-o" value="2">Архивы</option>
		<option data-icon="fa-spinner" value="3">Gif</option>
		<option data-icon="fa-file-image-o" value="4">Изображения</option>
		<option data-icon="fa-file-audio-o" value="5">Аудио</option>
		<option data-icon="fa-file-video-o" value="6">Видео</option>
		<option data-icon="fa-book" value="7">Электронные книги</option>
		<option data-icon="fa-file" value="8">Прочее</option>
	</select>
	</div>
	
</div>

          <h2 class="sub-header"><i class="fa fa-file"></i> Документы</h2>
          <div class="container" id="docs-list">
E;

	$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 0;
	$npage = $page+1;
	$offset_page = ($page > 0) ? $cfg['perpage_docs']*$page : 0;

	mb_internal_encoding("UTF-8");
$r = $db->query("SELECT * FROM vk_docs WHERE local_path != '' ORDER BY date DESC LIMIT {$offset_page},{$cfg['perpage_docs']}");
while($row = $db->return_row($r)){
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
		<a class="various-local" href="{$row['local_path']}" data-caption="{$row['title']}" data-fancybox="images"></a>
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

print <<<E
			<div class="paginator-next" style="display:none;"><span class="paginator-val">{$npage}</span><a href="ajax/docs-paginator.php?page={$npage}">следующая страница</a></div>
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
	var notload = false;
	var list = jQuery("#docs-list");
	
	// Default options
	var page = 1;
	var type = 'all';
	
	// Bootstrip select
	$('.selectpicker').selectpicker({
		iconBase: 'fa',
		tickIcon: 'fa-check'
	});
	
	jQuery('.docs-filter-btn').click(function(){ jQuery('.docs-filter-box').show(); });
	
	// Hash URL commands
	urlCommands.bind('type', function(id) { type = id; jQuery("#f-type").selectpicker('val',id); });
	
	// Not default options -> reload
	if(type != 'all'){
		urlCommands.urlPush({page:0});
		docs_reload();
	}
	
	urlCommands.bind('page', function(id) {
		if($.isNumeric(id) && id >= 2){
			notload = true;
			for(i=2;i<=id;i++){
				jQuery.ajax({
					async : false,
					method : "GET",
					url : "ajax/docs-paginator.php?page="+i+"&type="+type+""
				}).done( function(data){
					jQuery(".paginator-next").remove();
					list.append(data);
				});
			}
			notload = false;
		}
	});
	
	// If filter command changed, update url and reload data with new filter
	jQuery("#f-type").on('change', function(){
		urlCommands.urlPush({type:this.value});
		if(type != this.value){
			type = this.value;
			urlCommands.urlPush({page:0});
			docs_reload();
		}
	});
	
	function docs_reload(){
		jQuery.ajax({
			async : false,
			method : "GET",
			url : "ajax/docs-paginator.php?page=0&type="+type+""
		}).done( function(data){
			jQuery("#docs-list").html(data);
			jscroller();
		});
		doc_gif();
	}
	
	if(notload == false){
		jscroller();
	}

	$('.various-local').fancybox({
		{$fancybox_options}
	});
	
	$(".tip").tooltip();
	
	doc_gif();

});
</script>
E;

if(!$cfg['pj']){
	print $skin->footer(array('extend'=> $ex_bot));
} else {
	print $ex_bot;
}

$db->close($res);

?>