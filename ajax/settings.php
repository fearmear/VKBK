<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('../cfg.php');

// Get DB
require_once(ROOT.'classes/db.php');
$db = new db();
$res = $db->connect($cfg['host'],$cfg['user'],$cfg['pass'],$cfg['base']);

// Get Skin
require_once(ROOT.'classes/skin.php');
$skin = new skin();

print $skin->header_ajax();

$settings = array(
	'auto-queue-photo'=>0,
	'auto-queue-audio'=>0,
	'play-local-video'=>0,
	'start-local-video'=>0
);

$q = $db->query("SELECT * FROM vk_status");
while($row = $db->return_row($q)){
	if(array_key_exists($row['key'],$settings)){
		$row['val'] == '1' ? $row['val'] = 'checked' : $row['val'] = '';
		$settings[$row['key']] = $row['val'];
	}
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
							<div class="clearfix">
							<div style="float:left;margin:0 10px 10px 0">
								<input id="auto-queue-photo" type="checkbox" data-toggle="toggle" data-size="small" data-onstyle="info" {$settings['auto-queue-photo']}>
							</div>
							<div>Автоматически переходить к очереди закачек после синхронизации фотографий.</div>
							</div>
							<hr/>
							<div class="clearfix">
							<div style="float:left;margin:0 10px 10px 0">
								<input id="auto-queue-audio" type="checkbox" data-toggle="toggle" data-size="small" data-onstyle="info" {$settings['auto-queue-audio']}>
							</div>
							<div>Автоматически переходить к очереди закачек после синхронизации аудиофайлов.</div>
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
							<div class="clearfix">
							<div style="float:left;margin:0 10px 10px 0">
								<input id="play-local-video" type="checkbox" data-toggle="toggle" data-size="small" data-onstyle="info" {$settings['play-local-video']}>
							</div>
							<div>Воспроизводить локальное видео вместо онлайн-плеера.</div>
							</div>
							<hr/>
							<div class="clearfix">
							<div style="float:left;margin:0 10px 10px 0">
								<input id="start-local-video" type="checkbox" data-toggle="toggle" data-size="small" data-onstyle="info" {$settings['start-local-video']}>
							</div>
							<div>Автоматически воспроизводить локальное видео.</div>
							</div>
						</div>
					</div>
				</div>
			</div>
          </div>
</div>
E;


print $skin->footer_ajax();

$db->close($res);

?>