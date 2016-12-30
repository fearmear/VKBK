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

// Get local counters for top menu
$lc = $db->query_row("SELECT * FROM vk_counters");

$ex_top = <<<E
<link rel="stylesheet" href="css/bootstrap2-toggle.css" type="text/css" media="screen" />
E;

print $skin->header(array('extend'=>$ex_top));
print $skin->navigation($lc);

$settings = array();

$q = $db->query("SELECT * FROM vk_status");
while($row = $db->return_row($q)){
	if($row['key'] == 'auto-queue-photo'){ $row['val'] == '1' ? $row['val'] = 'checked' : $row['val'] = '';	}
	if($row['key'] == 'auto-queue-audio'){ $row['val'] == '1' ? $row['val'] = 'checked' : $row['val'] = '';	}
	if($row['key'] == 'play-local-video'){ $row['val'] == '1' ? $row['val'] = 'checked' : $row['val'] = '';	}
	$settings[$row['key']] = $row['val'];
}

print <<<E
<div class="container">
          <h2 class="sub-header"><i class="fa fa-sliders"></i> Настройки</h2>
          <div class="container">
			<div class="row">
				<div class="col-sm-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Редиректы</h3>
						</div>
						<div class="panel-body">
							Автоматически переходить к очереди закачек после синхронизации фотографий.<br/>
							<div style="text-align:right;">
								<input id="auto-queue-photo" type="checkbox" data-toggle="toggle" data-size="small" data-onstyle="info" {$settings['auto-queue-photo']}>
							</div>
							<hr/>
							Автоматически переходить к очереди закачек после синхронизации аудиофайлов.<br/>
							<div style="text-align:right;">
								<input id="auto-queue-audio" type="checkbox" data-toggle="toggle" data-size="small" data-onstyle="info" {$settings['auto-queue-audio']}>
							</div>
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Видео</h3>
						</div>
						<div class="panel-body">
							Воспроизводить локальное видео вместо онлайн-плеера.<br/>
							<div style="text-align:right;">
								<input id="play-local-video" type="checkbox" data-toggle="toggle" data-size="small" data-onstyle="info" {$settings['play-local-video']}>
							</div>
						</div>
					</div>
				</div>
			</div>
          </div>
</div>
E;

$ex_bot = <<<E
<script type="text/javascript" src="js/bootstrap2-toggle.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('#auto-queue-photo').change(function() {
		$.get("ajax/settings-save-bool.php", { "auto-queue-photo":$(this).prop('checked') } );
    });
	$('#auto-queue-audio').change(function() {
		$.get("ajax/settings-save-bool.php", { "auto-queue-audio":$(this).prop('checked') } );
    });
	$('#play-local-video').change(function() {
		$.get("ajax/settings-save-bool.php", { "play-local-video":$(this).prop('checked') } );
    });
});
</script>

E;
print $skin->footer(array('extend'=> $ex_bot));

$db->close($res);

?>