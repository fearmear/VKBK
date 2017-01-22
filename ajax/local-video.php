<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check do we have all needed GET data
$video = 0;
if(isset($_GET['id']) && is_numeric($_GET['id'])){
	$id = intval($_GET['id']);
	if($id > 0){ $video = $id; }
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

$ex_top = <<<E
<link rel="stylesheet" href="jplayer/skin/vkbk/css/jplayer.vkbk.css" type="text/css" media="screen" />
<style type="text/css">
body {padding:0px;margin:0px;overflow:hidden;}
.wall-box{margin-bottom:0;}
</style>
E;

print $skin->header(array('extend'=>$ex_top));

print <<<E
<div class="col-sm-12 wall-body" style="padding:0;">
    <div class="col-sm-12" id="wall-list" style="padding:0;">

<div id="jp_container_1" class="jp-video" role="application" aria-label="media player" style="margin:0 auto;border:0;">
	<div class="jp-type-single">
		<div id="jquery_jplayer_1" class="jp-jplayer" style="margin:0 auto;position: absolute;"></div>
		<div class="jp-gui jp-interface" style="position:relative;background-color:#f1f2f4;margin:0 auto;bottom:0;">
			<div class="jp-controls">
				<button class="jp-previous" role="button" tabindex="0" style="display:none;"><i class="fa fa-backward"></i></button>
				<button class="jp-play" role="button" tabindex="0" onClick="javascript:sptogle();"><i class="fa fa-play"></i><i class="fa fa-pause" style="display:none;"></i></button>
				<button class="jp-stop" role="button" tabindex="0" style="display:none;"><i class="fa fa-stop"></i></button>
				<button class="jp-next" role="button" tabindex="0" style="display:none;"><i class="fa fa-forward"></i></button>
			</div>
			<div class="jp-progress">
				<div class="jp-seek-bar">
					<div class="jp-play-bar"></div>
				</div>
			</div>
			<div class="jp-volume-controls">
				<button class="jp-mute" role="button" tabindex="0"><i class="fa fa-volume-off"></i></button>
				<button class="jp-volume-max" role="button" tabindex="0"><i class="fa fa-volume-up"></i></button>
				<div class="jp-volume-bar">
					<div class="jp-volume-bar-value"></div>
				</div>
			</div>
			<div class="jp-time-holder">
				<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>&nbsp;/&nbsp;
				<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
			</div>
			<div class="jp-toggles">
				<button class="jp-repeat" role="button" tabindex="0"><i class="fa fa-retweet"></i></button>
				<button class="jp-shuffle" role="button" tabindex="0" style="display:none;"><i class="fa fa-random"></i></button>
			</div>
		</div>
		<div class="jp-no-solution">
			<span>Update Required</span>
			To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
		</div>
	</div>
</div>
E;

$list = $db->query_row("SELECT * FROM vk_videos WHERE `id` = {$video}");

// Rewrite if you plan to store content outside of web directory and will call it by Alias
if($cfg['vhost_alias'] == true && substr($list['local_path'],0,4) != 'http'){
	$list['local_path'] = preg_replace("/^\//","",$f->windows_path_alias($list['local_path'],'video'));
}
if($cfg['vhost_alias'] == true && substr($list['preview_path'],0,4) != 'http'){
	$list['preview_path'] = preg_replace("/^\//","",$f->windows_path_alias($list['preview_path'],'video'));
}
$list['title'] = trim(preg_replace('/\"/','\\"',$list['title']));
$format = '';
$solution = "html";
if($list['local_format'] == 'flv') {
	$solution = "flash";
	$format = 'flv: "'.$cfg['vkbk_url'].$list['local_path'].'",'; 	}
if($list['local_format'] == 'webm'){
	$format = 'webmv: "'.$cfg['vkbk_url'].$list['local_path'].'",'; }
if($list['local_format'] == 'mp4') {
	$format = 'm4v: "'.$cfg['vkbk_url'].$list['local_path'].'",'; 	}

$auto_start = '';
$opt = $db->query_row("SELECT val FROM vk_status WHERE `key` = 'start-local-video'");
if($opt['val'] == 1){
	$auto_start = ".jPlayer('play')";
}

print <<<E
          </div>
</div>
<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="jplayer/jquery.jplayer.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$("#jquery_jplayer_1").jPlayer({
		ready: function () {
			$(this).jPlayer("setMedia", {
				{$format}
				poster: "{$cfg['vkbk_url']}{$list['preview_path']}"
			}){$auto_start};
		},
		size: {
			width: jQuery(document).width(),
			height: jQuery(document).height(),
		},
		swfPath: "jplayer",
        cssSelectorAncestor: "#jp_container_1",
		backgroundColor: "#e8e8e8",
		solution: "{$solution}",
        supplied: "webmv, ogv, m4v, flv",
		preload: "metadata",
		volume: 1,
		muted: false,
        useStateClassSkin: true,
        autoBlur: false,
        //smoothPlayBar: true,
        keyEnabled: true,
        remainingDuration: false,
        toggleDuration: true,
		autohide: {
			restored: true,
			hold: 5000
		},
		keyBindings: {
			play: {
				key: 32, // space
				fn: function(f) {
					jQuery(".jp-play .fa-play").toggle();
					jQuery(".jp-play .fa-pause").toggle();
					if(f.status.paused == true) {
						f.play();
					} else {
						f.pause();
					}
				}
			},
			fullScreen: {
				key: 70, // f
				fn: function(f) {
					if(f.status.video || f.options.audioFullScreen) {
						f._setOption("fullScreen", !f.options.fullScreen);
					}
				}
			},
			muted: {
				key: 77, // m
				fn: function(f) {
					f._muted(!f.options.muted);
				}
			},
			volumeUp: {
				key: 190, // .
				fn: function(f) {
					f.volume(f.options.volume + 0.1);
				}
			},
			volumeDown: {
				key: 188, // ,
				fn: function(f) {
					f.volume(f.options.volume - 0.1);
				}
			},
			loop: {
				key: 82, // r
				fn: function(f) {
					f._loop(!f.options.loop);
				}
			}
		}
    });
});
$(window).resize(function(){
	$("#jquery_jplayer_1").jPlayer({
		size: {
			width: jQuery(window).width(),
			height: jQuery(window).height(),
		}
	});
});
function sptogle(){
	jQuery(".jp-play .fa-play").toggle();
	jQuery(".jp-play .fa-pause").toggle();
}
</script>
</body>
</html>
E;

$db->close($res);

?>