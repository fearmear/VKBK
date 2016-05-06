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

$offset_page = ($page > 0) ? $cfg['perpage_wall']*$page : 0;
// Get 1 more post to see do we have something on the next page
$perpage = $cfg['perpage_wall']+1;
$next = 0;

$r = $db->query("SELECT * FROM vk_wall WHERE is_repost = 0 ORDER BY date DESC LIMIT {$offset_page},{$perpage}");
while($row = $db->return_row($r)){
	if($next < $cfg['perpage_wall']){
		$repost_body = '';
		$rrp_body = '';
		
		// Post have a repost?
		if($row['repost'] > 0){
			$rp = $db->query_row("SELECT * FROM vk_wall WHERE id = {$row['repost']}");
			// Post have a rerepost?
			if($rp['repost'] > 0){
				$rrp = $db->query_row("SELECT * FROM vk_wall WHERE id = {$rp['repost']}");
				$rrp_body = $f->wall_show_post($rrp,true,'');
			}
			$repost_body = $f->wall_show_post($rp,true,$rrp_body);
			
		} // repost body end
		
		// Make post
		print $f->wall_show_post($row,false,$repost_body);
		
	} // End of while perpage body
	// Increase NEXT so if we load a full page we would have in the end NEXT = perpage+1
	// Otherwise if next would be lower or equal perpage there is no result for the next page
	$next++;
} // End of while

if($next > $cfg['perpage_wall']){
	$page++;
	print '<div class="paginator-next" style="display:none;"><a href="ajax/wall-paginator.php?page='.$page.'">следующая страница</a></div>';
}

$db->close($res);

?>