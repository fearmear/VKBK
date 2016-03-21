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

print $skin->header(array('extend'=>''));
print $skin->navigation($lc);

$bar_total = $db->query_row("SELECT COUNT(*) as p FROM vk_photos WHERE album_id > -9000");
$bar = $db->query_row("SELECT COUNT(*) as p FROM vk_photos WHERE album_id > -9000 AND `in_queue` = 1");
$bar_queue['p'] = $bar['p'];
$per = $bar_total['p']/100;
if($bar_total['p'] > 0){
$done['pp'] = round(($bar_total['p'] - $bar_queue['p']) / $per, 2);
$done['p'] = ceil($done['pp']);
} else { $done['p'] = $done['pp'] = 0; }

$bar_total = $db->query_row("SELECT COUNT(*) as m FROM vk_music WHERE `deleted` = 0");
$bar = $db->query_row("SELECT COUNT(*) as m FROM vk_music WHERE `in_queue` = 1");
$bar_queue['m'] = $bar['m'];
$per = $bar_total['m']/100;
if($bar_total['m'] > 0){
$done['mm'] = round(($bar_total['m'] - $bar_queue['m']) / $per, 2);
$done['m'] = ceil($done['mm']);
} else { $done['m'] = $done['mm'] = 0; }

$bar_total = $db->query_row("SELECT COUNT(*) as v FROM vk_videos WHERE `deleted` = 0");
$bar = $db->query_row("SELECT COUNT(*) as v FROM vk_videos WHERE `in_queue` = 1");
$bar_queue['v'] = $bar['v'];
$per = $bar_total['v']/100;
if($bar_total['v'] > 0){
$done['vv'] = round(($bar_total['v'] - $bar_queue['v']) / $per, 2);
$done['v'] = ceil($done['vv']);
} else { $done['v'] = $done['vv'] = 0; }

$all_queue = $bar_queue['p'] + $bar_queue['m'] + $bar_queue['v'];

print <<<E
<div class="container">
          <h2 class="sub-header"><i class="fa fa-cloud-download"></i> Очередь закачки {$all_queue}</h2>
          <div class="table-responsive">

E;

// Show last queue records
$show = 25;

// Make a progress bars
print <<<E
<div class="row" style="margin:0;">
<div class="col-sm-2"><i class="fa fa-image"></i> Фотографии <span class="label label-default">{$done['pp']}%</span></div>
<div class="col-sm-10">
<div class="progress">
	<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{$done['p']}" aria-valuemin="0" aria-valuemax="100" style="width: {$done['p']}%"><span class="sr-only">{$done['p']}% Complete</span></div>
</div>
</div>
</div>
E;

print <<<E
<div class="row" style="margin:0;">
<div class="col-sm-2"><i class="fa fa-music"></i> Аудиозаписи <span class="label label-default">{$done['mm']}%</span></div>
<div class="col-sm-10">
<div class="progress">
	<div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="{$done['m']}" aria-valuemin="0" aria-valuemax="100" style="width: {$done['m']}%"><span class="sr-only">{$done['m']}% Complete</span></div>
</div>
</div>
</div>
E;

print <<<E
<div class="row" style="margin:0;">
<div class="col-sm-2"><i class="fa fa-film"></i> Видеозаписи <span class="label label-default">{$done['vv']}%</span></div>
<div class="col-sm-10">
<div class="progress">
	<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{$done['v']}" aria-valuemin="0" aria-valuemax="100" style="width: {$done['v']}%"><span class="sr-only">{$done['v']}% Complete</span></div>
</div>
</div>
</div>
E;
unset($done);

$skip_list = isset($_GET['skip']) ? preg_replace("/[^0-9\,]/","",$_GET['skip']) : '';

if(isset($_GET['id']) && isset($_GET['t'])){
	$queue_id = is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
	$don = false;
	mb_internal_encoding("UTF-8");
	
	// Pictures
	if($queue_id > 0 && $_GET['t']=='p'){
		$don = true;
		// Get photo info
		$q = $db->query_row("SELECT * FROM vk_photos WHERE `id` = {$queue_id}");//.($skip_list != '' ? "AND `id` NOT IN (".$skip_list.")" : "")
		if($q['uri'] != ''){
			
			// Are you reagy kids? YES Capitan Curl!
			require_once(ROOT.'classes/curl.php');
			$c = new cu();
			$c->curl_on();
			preg_match("/[^\/]+$/",$q['uri'],$n);
			$f = date("Y-m",$q['date_added']);
			
			$out = $c->curl_req(array(
					'uri' => $q['uri'],
					'method'=>'',
					'return'=>1
			));
			
			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html'){
				$saved = $c->file_save(array('path'=>$cfg['photo_path'].$f.'/','name'=>$n[0]),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;
					$q = $db->query("UPDATE vk_photos SET `in_queue` = 0, `date_done` = ".time().", `path` = '".$cfg['photo_path'].$f."/".$n[0]."', `saved` = 1, `hash` = '".md5_file($cfg['photo_path'].$f."/".$n[0])."' WHERE `id` = ".$queue_id."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT id FROM vk_photos WHERE album_id > -9000 AND `in_queue` = 1 ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через ".$cfg['sync_photo_next_cd']." сек.",$cfg['vkbk_url']."queue.php?t=p&id=".$nrow['id']."&auto=1",$cfg['sync_photo_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
				}
			} else {
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста для {$nam}</div>
E;
			}
			
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = P
	
	// Music
	if($queue_id > 0 && $_GET['t']=='m'){
		$don = true;
		// Get photo info
		$q = $db->query_row("SELECT * FROM vk_music WHERE `id` = {$queue_id}");
		if($q['uri'] != ''){
			
			// Are you reagy kids? YES Capitan Curl!
			require_once(ROOT.'classes/curl.php');
			$c = new cu();
			$c->curl_on();
			$q['uri'] = preg_replace("/\?extra\=.*/","",$q['uri']);
			preg_match("/[^\.]+$/",$q['uri'],$n);
			if(mb_strlen($q['title']) > 200){ $q['title'] = mb_substr($row['artist'],0,200); }
			$nam = $c->clean_name($q['artist'].' - '.$q['title'].' ['.$q['id'].'].'.$n[0]);
			$win = $c->win_name($nam);
			// Double check this f**kn' filename
			// If filename was converted for windows we need revert codepage to UTF-8 before DB insert
			// Or hell will be on earth... ><
			if($win[0] == true){
				$cnam = iconv("CP1251","UTF-8",$win[1]);
			} else {
				$cnam = $win[1];
			}
			$fnam = $win[1];
			
			$out = $c->curl_req(array(
					'uri' => $q['uri'],
					'method'=>'',
					'return'=>1
			));

			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html'){
				$saved = $c->file_save(array('path'=>$cfg['music_path'],'name'=>$fnam),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл <b>{$nam}</b> сохранен</div>
E;
				
					$q = $db->query("UPDATE vk_music SET `in_queue` = 0, `date_done` = ".time().", `path` = '".$cfg['music_path'].mysql_real_escape_string($cnam)."', `saved` = 1, `hash` = '".md5_file($cfg['music_path'].$fnam)."' WHERE `id` = ".$queue_id."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT id FROM vk_music WHERE `in_queue` = 1 ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через ".$cfg['sync_music_next_cd']." сек.",$cfg['vkbk_url']."queue.php?t=m&id=".$nrow['id']."&auto=1",$cfg['sync_music_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла {$nam}</div>
E;
				}
			} else {
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста для {$nam}</div>
E;
			}
			
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = M
	
	// Videos
	if($queue_id > 0 && $_GET['t']=='v'){
		$don = true;
		// Get video info
		$q = $db->query_row("SELECT * FROM vk_videos WHERE `id` = {$queue_id}");
		if($q['preview_uri'] != ''){
			
			// Are you reagy kids? YES Capitan Curl!
			require_once(ROOT.'classes/curl.php');
			$c = new cu();
			$c->curl_on();
			preg_match("/[^\/]+$/",$q['preview_uri'],$n);
			$out = $c->curl_req(array(
					'uri' => $q['preview_uri'],
					'method'=>'',
					'return'=>1
			));
			
			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html'){
				$saved = $c->file_save(array('path'=>$cfg['video_path'],'name'=>$n[0]),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл превью сохранен</div>
E;
					$q = $db->query("UPDATE vk_videos SET `in_queue` = 0, `date_done` = ".time().", `preview_path` = '".$cfg['video_path'].$n[0]."' WHERE `id` = ".$queue_id."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT id FROM vk_videos WHERE `in_queue` = 1 ".($skip_list != '' ? "AND `id` NOT IN (".$skip_list.")" : "")." ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через ".$cfg['sync_video_next_cd']." сек.",$cfg['vkbk_url']."queue.php?t=v&id=".$nrow['id']."&auto=1".($skip_list != '' ? "&skip=".$skip_list : ""),$cfg['sync_video_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении превью файла</div>
E;
				}
			} else {
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить превью #{$queue_id} с удаленного хоста для {$n[0]}</div>
E;
				// Move ID to skip list & continue if server response is contain html
				if($_GET['auto'] == '1' && substr($out['content'],0,5) == '<html'){
						$skip_row = ($_GET['skip'] != '') ? $_GET['skip'].','.$queue_id : $queue_id;
						$nrow = $db->query_row("SELECT id FROM vk_videos WHERE `in_queue` = 1 && `id` < {$queue_id} ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Пропускаем #".$queue_id." следующий #".$nrow['id'].". Страница будет обновлена через ".$cfg['sync_music_error_cd']." сек.",$cfg['vkbk_url']."queue.php?t=v&id=".$nrow['id']."&auto=1&skip=".$skip_row."",$cfg['sync_music_error_cd']);
						}
				}
			}
			
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = V
	
	if($don == false) {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Неправильный тип или ID</div>
E;
	}
	
}

print <<<E
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
				  <th>URL</th>
				  <th>Добавлено</th>
				  <th style="text-align:center;">Сохранить</th>
                </tr>
              </thead>
              <tbody>
E;

$first['p'] = true;
if($bar_queue['p'] > 0){
	$r = $db->query("SELECT * FROM vk_photos WHERE `in_queue` = 1 ORDER BY date_added DESC LIMIT 0,{$show}");
	while($row = $db->return_row($r)){
		$row['date_added'] = date("Y-m-d H:i:s",$row['date_added']);
		// Add a autodownload for the first element in list
		if($first['p'] == true){
			$first['p'] = false;
			$auto = "&nbsp;&nbsp;<a href=\"queue.php?t=p&id={$row['id']}&auto=1\" style=\"font-size:130%;\" class=\"label label-success\" onClick=\"jQuery('#{$row['id']}').hide();return true;\" title=\"Скачать автоматически\"><b class=\"fa fa-repeat\"></b></a>";
		} else { $auto = ''; }
print <<<E
<tr>
  <td>{$row['id']}</td>
  <td><a href="{$row['uri']}" target="_blank">{$row['uri']}</a></td>
  <td>{$row['date_added']}</td>
  <td style="text-align:center;"><a href="queue.php?t=p&id={$row['id']}" style="font-size:130%;" class="label label-success" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fa fa-arrow-circle-up"></b></a>{$auto}</td>
</tr>
E;
	}
}

$first['m'] = true;
if($bar_queue['m'] > 0){
	// Set default encoding for correct filenames
	mb_internal_encoding("UTF-8");
	$r = $db->query("SELECT * FROM vk_music WHERE `in_queue` = 1 ORDER BY date_added DESC LIMIT 0,{$show}");
	while($row = $db->return_row($r)){
		$row['date_added'] = date("Y-m-d H:i:s",$row['date_added']);
		//$row['uri_a'] = preg_replace("/\?extra\=.*/","",$row['uri']);
		if(mb_strlen($row['title']) > 50){ $row['title'] = mb_substr($row['title'],0,50).'...'; }
		if(mb_strlen($row['artist']) > 50){ $row['artist'] = mb_substr($row['artist'],0,50).'...'; }
		$duration = $skin->seconds2human($row['duration']);
		
		// Add a autodownload for the first element in list
		if($first['m'] == true){
			$first['m'] = false;
			$auto = "&nbsp;&nbsp;<a href=\"queue.php?t=m&id={$row['id']}&auto=1\" style=\"font-size:130%;\" class=\"label label-warning\" title=\"Скачать автоматически\"><b class=\"fa fa-repeat\" onClick=\"jQuery('#{$row['id']}').hide();return true;\"></b></a>";
		} else { $auto = ''; }
print <<<E
<tr>
  <td>{$row['id']}</td>
  <td><a href="{$row['uri']}" target="_blank">[{$duration}] {$row['artist']} - {$row['title']}</a></td>
  <td>{$row['date_added']}</td>
  <td style="text-align:center;"><a href="queue.php?t=m&id={$row['id']}" style="font-size:130%;" class="label label-warning" id="{$row['id']}" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fa fa-arrow-circle-up"></b></a>{$auto}</td>
</tr>
E;
	}
}

$first['v'] = true;
if($bar_queue['v'] > 0){
	$r = $db->query("SELECT * FROM vk_videos WHERE `in_queue` = 1 ".($skip_list != '' ? "AND `id` NOT IN (".$skip_list.")" : "")." ORDER BY date_added DESC LIMIT 0,{$show}");
	while($row = $db->return_row($r)){
		$row['date_added'] = date("Y-m-d H:i:s",$row['date_added']);
		// Add a autodownload for the first element in list
		if($first['v'] == true){
			$first['v'] = false;
			$auto = "&nbsp;&nbsp;<a href=\"queue.php?t=v&id={$row['id']}&auto=1\" style=\"font-size:130%;\" class=\"label label-info\" onClick=\"jQuery('#{$row['id']}').hide();return true;\" title=\"Скачать автоматически\"><b class=\"fa fa-repeat\"></b></a>";
		} else { $auto = ''; }
print <<<E
<tr>
  <td>{$row['id']}</td>
  <td><a href="{$row['preview_uri']}" target="_blank">{$row['preview_uri']}</a></td>
  <td>{$row['date_added']}</td>
  <td style="text-align:center;"><a href="queue.php?t=v&id={$row['id']}" style="font-size:130%;" class="label label-info" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fa fa-arrow-circle-up"></b></a>{$auto}</td>
</tr>
E;
	}
}

if($all_queue == 0) {
	print '<tr><td colspan="4" style="text-align:center;color:#bbb;">Очередь закачки пуста</td></tr>';
}

if($bar_queue['p'] > $show || $bar_queue['m'] > $show){
print <<<E
<tr>
  <td colspan="4">
	<div class="alert alert-info" role="alert">
E;
print 'И ещё';
	if(($bar_queue['p']-$show) > 0){
		print ' <strong>'.($bar_queue['p']-$show).'</strong> фотографий;';
	}
	if(($bar_queue['m']-$show) > 0){
		print ' <strong>'.($bar_queue['m']-$show).'</strong> аудиозаписей;';
	}

print <<<E
    </div>
  </td>
</tr>
E;
}

print <<<E
              </tbody>
            </table>
          </div>
</div>
E;

print $skin->footer(array('v'=>$version,'extend'=>''));

$db->close($res);

?>