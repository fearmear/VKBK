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

// Get local counters for top menu
$lc = $db->query_row("SELECT * FROM vk_counters");

if(!$cfg['pj']){
	print $skin->header(array('extend'=>''));
	print $skin->navigation($lc);
}

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

$bar_total = $db->query_row("SELECT COUNT(*) as at FROM vk_attach WHERE `uri` != '' AND `is_local` = 0");
$bar = $db->query_row("SELECT COUNT(*) as at FROM vk_attach WHERE `path` = '' AND `uri` != '' AND `is_local` = 0");
$bar_queue['at'] = $bar['at'];
$per = $bar_total['at']/100;
if($bar_total['at'] > 0){
$done['att'] = round(($bar_total['at'] - $bar_queue['at']) / $per, 2);
$done['at'] = ceil($done['att']);
} else { $done['at'] = $done['att'] = 0; }

$bar_total = $db->query_row("SELECT COUNT(*) as dc FROM vk_docs WHERE `deleted` = 0");
$bar = $db->query_row("SELECT COUNT(*) as dc FROM vk_docs WHERE `in_queue` = 1");
$bar_queue['dc'] = $bar['dc'];
$per = $bar_total['dc']/100;
if($bar_total['dc'] > 0){
$done['dcc'] = round(($bar_total['dc'] - $bar_queue['dc']) / $per, 2);
$done['dc'] = ceil($done['dcc']);
} else { $done['dc'] = $done['dcc'] = 0; }

$all_queue = $bar_queue['p'] + $bar_queue['m'] + $bar_queue['v'] + $bar_queue['at'] + $bar_queue['dc'];
$no_queue = true;

// Profiles & Groups
$pr = $db->query_row("SELECT COUNT(*) as c FROM `vk_profiles` WHERE `photo_path` = ''");
$all_queue += $pr['c'];
$gr = $db->query_row("SELECT COUNT(*) as c FROM `vk_groups` WHERE `photo_path` = ''");
$all_queue += $gr['c'];

// Fix for counter if queue active
if($all_queue > 0 && isset($_GET['t'])){ $all_queue--; }

print <<<E
<div class="container">
          <h2 class="sub-header"><i class="fa fa-cloud-download"></i> Очередь закачки {$all_queue}</h2>
          <div class="table-responsive">
			<div class="white-box" style="padding:20px 0;margin-bottom:10px;white-space:nowrap;">
E;

// Show last queue records
$show = 25;
$bar = array();

// Make a progress bars
// Photo
$bar[0] = array('fa' => 'image','name' => 'Фотографии','perx' => $done['pp'],'per' => $done['p'],'bar' => 'success');

// Audio
$bar[1] = array('fa' => 'music','name' => 'Аудиозаписи','perx' => $done['mm'],'per' => $done['m'],'bar' => 'warning');

// Video
$bar[2] = array('fa' => 'film','name' => 'Видеозаписи','perx' => $done['vv'],'per' => $done['v'],'bar' => 'info');

// Attachments
$bar[3] = array('fa' => 'paperclip','name' => 'Вложения','perx' => $done['att'],'per' => $done['at'],'bar' => 'primary');

// Documents
$bar[4] = array('fa' => 'file-o','name' => 'Документы','perx' => $done['dcc'],'per' => $done['dc'],'bar' => 'danger');

foreach($bar as $bark => $barv){
	print $skin->queue_progress_bar($barv);
}

print <<<E
<div class="clearfix"></div>
</div>
E;
unset($done);

$skip_list = isset($_GET['skip']) ? preg_replace("/[^0-9\,]/","",$_GET['skip']) : '';
if(!isset($_GET['auto'])){ $_GET['auto'] = false; } 

if(isset($_GET['id']) && isset($_GET['t'])){
	$queue_id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? intval($_GET['id']) : 0;
	$queue_oid = (isset($_GET['oid']) && is_numeric($_GET['oid'])) ? intval($_GET['oid']) : 0;
	$don = false;
	$error_code = '';
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
			
			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
				$saved = $c->file_save(array('path'=>$cfg['photo_path'].$f.'/','name'=>$n[0]),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;
					$q = $db->query("UPDATE vk_photos SET `in_queue` = 0, `date_done` = ".time().", `path` = '".$cfg['photo_path'].$f."/".$n[0]."', `saved` = 1, `hash` = '".md5_file($cfg['photo_path'].$f."/".$n[0])."' WHERE `id` = ".$queue_id."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT id FROM vk_photos WHERE album_id > -9000 AND `in_queue` = 1 ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=p&id=".$nrow['id']."&auto=1",$cfg['sync_photo_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
				}
			} else {
				// If error, let's try to see wtf is going on
				if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
					$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
					if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
				}
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
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
		// Get audio info
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

			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
				$saved = $c->file_save(array('path'=>$cfg['music_path'],'name'=>$fnam),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл <b>{$nam}</b> сохранен</div>
E;
				
					$q = $db->query("UPDATE vk_music SET `in_queue` = 0, `date_done` = ".time().", `path` = '".$cfg['music_path'].$db->real_escape($cnam)."', `saved` = 1, `hash` = '".md5_file($cfg['music_path'].$fnam)."' WHERE `id` = ".$queue_id."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT id FROM vk_music WHERE `in_queue` = 1 ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_music_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=m&id=".$nrow['id']."&auto=1",$cfg['sync_music_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла {$nam}</div>
E;
				}
			} else {
				// If error, let's try to see wtf is going on
				if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
					$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
					if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
				}
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста для {$nam}{$error_code}</div>
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
			
			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
				$saved = $c->file_save(array('path'=>$cfg['video_path'],'name'=>$n[0]),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл превью сохранен</div>
E;
					$q = $db->query("UPDATE vk_videos SET `in_queue` = 0, `date_done` = ".time().", `preview_path` = '".$cfg['video_path'].$n[0]."' WHERE `id` = ".$queue_id."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT id FROM vk_videos WHERE `in_queue` = 1 ".($skip_list != '' ? "AND `id` NOT IN (".$skip_list.")" : "")." ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_video_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=v&id=".$nrow['id']."&auto=1".($skip_list != '' ? "&skip=".$skip_list : ""),$cfg['sync_video_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении превью файла</div>
E;
				}
			} else {
				// If error, let's try to see wtf is going on
				if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
					$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
					if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
				}
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить превью #{$queue_id} с удаленного хоста для {$n[0]}{$error_code}</div>
E;
				// Move ID to skip list & continue if server response is contain html
				if($_GET['auto'] == '1' && substr($out['content'],0,5) == '<html'){
						$skip_row = ($_GET['skip'] != '') ? $_GET['skip'].','.$queue_id : $queue_id;
						$nrow = $db->query_row("SELECT id FROM vk_videos WHERE `in_queue` = 1 && `id` < {$queue_id} ORDER BY date_added DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Пропускаем #".$queue_id." следующий #".$nrow['id'].". Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_music_error_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=v&id=".$nrow['id']."&auto=1&skip=".$skip_row."",$cfg['sync_music_error_cd']);
						}
				}
			}
			
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = V
	
	// Documents
	if($queue_id > 0 && $_GET['t']=='dc'){
		$don = true;
		// Get document info
		$q = $db->query_row("SELECT * FROM vk_docs WHERE `id` = {$queue_id}");
		if($q['uri'] != ''){
			
			// Are you reagy kids? YES Capitan Curl!
			require_once(ROOT.'classes/curl.php');
			$c = new cu();
			$c->curl_on();
			$f = date("Y-m",$q['date']);
			$out = $c->curl_req(array(
					'uri' => $q['uri'],
					'method'=>'',
					'return'=>1
			));
			
			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
				$saved = $c->file_save(array('path'=>$cfg['docs_path'].$f.'/','name'=>$q['id'].'.'.$q['ext']),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;

					$prev_q = '';
					if(($q['type'] == 3 || $q['type'] == 4) && $q['preview_uri'] != ''){
						$out_pre = $c->curl_req(array(
							'uri' => $q['preview_uri'],
							'method'=>'',
							'return'=>1
						));
						if($out_pre['err'] == 0 && $out_pre['errmsg'] == '' && $out_pre['content'] != '' && substr($out_pre['content'],0,5) != '<html' && substr($out_pre['content'],0,9) != '<!DOCTYPE'){
							preg_match("/[^\.]+$/",$q['preview_uri'],$np);
							$saved_pre = $c->file_save(array('path'=>$cfg['docs_path'].'preview/','name'=>$q['id'].'.'.$np['0']),$out_pre['content']);
							if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Превью сохранено</div>
E;
								$prev_q = ", `preview_path` = '".$cfg['docs_path']."preview/".$q['id'].".".$np[0]."'";
							}
						}
					}

					$q = $db->query("UPDATE vk_docs SET `in_queue` = 0, `local_path` = '".$cfg['docs_path'].$f."/".$q['id'].".".$q['ext']."'".$prev_q." WHERE `id` = ".$queue_id."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT id FROM vk_docs WHERE `in_queue` = 1 ORDER BY date DESC");
						if($nrow['id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_docs_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=dc&id=".$nrow['id']."&auto=1",$cfg['sync_docs_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
				}
			} else {
				// If error, let's try to see wtf is going on
				if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
					$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
					if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
				}
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
E;
			}
			
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = DC
	
	// Profiles
	if($queue_id > 0 && $_GET['t']=='pr'){
		$don = true;
		// Get photo info
		$q = $db->query_row("SELECT * FROM vk_profiles WHERE `id` = {$queue_id}");
		if($q['photo_uri'] != ''){
			
			// Get file name
			preg_match("/[^\.]+$/",$q['photo_uri'],$n);
			
			// Check do we have this file already ( useful if you are developer and pucked up attachments DB :D )
			if(is_file(ROOT.'data/profiles/'.$queue_id.'.'.$n[0])){
print <<<E
<div class="alert alert-info" role="alert"><i class="fa fa-file"></i> Файл найден локально</div>
E;
				$q = $db->query("UPDATE vk_profiles SET `photo_path` = '".$queue_id.".".$n[0]."' WHERE `id` = ".$queue_id."");
				
				if($_GET['auto'] == '1'){
					$nrow = $db->query_row("SELECT id FROM vk_profiles WHERE `photo_path` = ''");
					if($nrow['id'] > 0){
						print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_found_local']."</span> сек.",$cfg['vkbk_url']."queue.php?t=pr&id=".$nrow['id']."&auto=1",$cfg['sync_found_local']);
					}
				}
			} else {
				
				// Are you reagy kids? YES Capitan Curl!
				require_once(ROOT.'classes/curl.php');
				$c = new cu();
				$c->curl_on();
				
				$out = $c->curl_req(array(
						'uri' => $q['photo_uri'],
						'method'=>'',
						'return'=>1
				));
			
				if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
					$saved = $c->file_save(array('path'=>ROOT.'data/profiles/','name'=>$queue_id.'.'.$n[0]),$out['content']);
					if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;
						$q = $db->query("UPDATE vk_profiles SET `photo_path` = '".$queue_id.".".$n[0]."' WHERE `id` = ".$queue_id."");
					
						if($_GET['auto'] == '1'){
							$nrow = $db->query_row("SELECT id FROM vk_profiles WHERE `photo_path` = ''");
							if($nrow['id'] > 0){
								print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=pr&id=".$nrow['id']."&auto=1",$cfg['sync_photo_next_cd']);
							}
						}
						
					} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
					}
				} else {
					// If error, let's try to see wtf is going on
					if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
						$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
						if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
					}
					// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
E;
				}
			} // end of local file check fail
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = PR
	
	// Groups
	if($queue_id > 0 && $_GET['t']=='gr'){
		$don = true;
		// Get photo info
		$q = $db->query_row("SELECT * FROM vk_groups WHERE `id` = {$queue_id}");
		if($q['photo_uri'] != ''){
			
			// Get file name
			preg_match("/[^\.]+$/",$q['photo_uri'],$n);
			
			// Check do we have this file already ( useful if you are developer and pucked up attachments DB :D )
			if(is_file(ROOT.'data/groups/'.$queue_id.'.'.$n[0])){
print <<<E
<div class="alert alert-info" role="alert"><i class="fa fa-file"></i> Файл найден локально</div>
E;
				$q = $db->query("UPDATE vk_groups SET `photo_path` = '".$queue_id.".".$n[0]."' WHERE `id` = ".$queue_id."");
				
				if($_GET['auto'] == '1'){
					$nrow = $db->query_row("SELECT id FROM vk_groups WHERE `photo_path` = ''");
					if($nrow['id'] > 0){
						print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_found_local']."</span> сек.",$cfg['vkbk_url']."queue.php?t=gr&id=".$nrow['id']."&auto=1",$cfg['sync_found_local']);
					}
				}
			} else {
				
				// Are you reagy kids? YES Capitan Curl!
				require_once(ROOT.'classes/curl.php');
				$c = new cu();
				$c->curl_on();
				
				$out = $c->curl_req(array(
						'uri' => $q['photo_uri'],
						'method'=>'',
						'return'=>1
				));
				
				if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
					$saved = $c->file_save(array('path'=>ROOT.'data/groups/','name'=>$queue_id.'.'.$n[0]),$out['content']);
					if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;
						$q = $db->query("UPDATE vk_groups SET `photo_path` = '".$queue_id.".".$n[0]."' WHERE `id` = ".$queue_id."");
						
						if($_GET['auto'] == '1'){
							$nrow = $db->query_row("SELECT id FROM vk_groups WHERE `photo_path` = ''");
							if($nrow['id'] > 0){
								print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=gr&id=".$nrow['id']."&auto=1",$cfg['sync_photo_next_cd']);
							}
						}
						
					} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
					}
				} else {
					// If error, let's try to see wtf is going on
					if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
						$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
						if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
					}
					// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
E;
				}
			} // end of local file check fail
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = GR
	
	// Attach - Photo
	if($queue_id > 0 && $_GET['t']=='atph' && $queue_oid != 0){
		$don = true;
		// Get photo info
		$q = $db->query_row("SELECT * FROM vk_attach WHERE `attach_id` = {$queue_id} AND `owner_id` = {$queue_oid}");
		if($q['uri'] != ''){
			
			// Get file name
			preg_match("/[^\/]+$/",$q['uri'],$n);
			$f = date("Y-m",$q['date']);
			
			// Check do we have this file already ( useful if you are developer and pucked up attachments DB :D )
			if(is_file($cfg['photo_path'].'attach/'.$f.'/'.$n[0])){
print <<<E
<div class="alert alert-info" role="alert"><i class="fa fa-file"></i> Файл найден локально</div>
E;
				$q = $db->query("UPDATE vk_attach SET `path` = '".$cfg['photo_path']."attach/".$f."/".$n[0]."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
				
				if($_GET['auto'] == '1'){
					$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'photo' AND `is_local` = 0");
					if($nrow['attach_id'] > 0){
						print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_found_local']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atph&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_found_local']);
					}
				}
			} else {
			
				// Are you reagy kids? YES Capitan Curl!
				require_once(ROOT.'classes/curl.php');
				$c = new cu();
				$c->curl_on();
			
				$out = $c->curl_req(array(
						'uri' => $q['uri'],
						'method'=>'',
						'return'=>1
				));
			
				if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
					$saved = $c->file_save(array('path'=>$cfg['photo_path'].'attach/'.$f.'/','name'=>$n[0]),$out['content']);
					if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;
						$q = $db->query("UPDATE vk_attach SET `path` = '".$cfg['photo_path']."attach/".$f."/".$n[0]."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
						
						if($_GET['auto'] == '1'){
							$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'photo' AND `is_local` = 0");
							if($nrow['attach_id'] > 0){
								print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atph&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_photo_next_cd']);
							}
						}
					
					} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
					}
				} else {
					// If error, let's try to see wtf is going on
					if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
						$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
						if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
					}
					// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
E;
				}
			} // end of local file check fail
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = ATPH
	
	// Attach - Video (preview)
	if($queue_id > 0 && $_GET['t']=='atvi' && $queue_oid != 0){
		$don = true;
		// Get video preview info
		$q = $db->query_row("SELECT * FROM vk_attach WHERE `attach_id` = {$queue_id} AND `owner_id` = {$queue_oid}");
		if($q['uri'] != ''){
			
			// Get file name
			preg_match("/[^\/]+$/",$q['uri'],$n);
			$f = date("Y-m",$q['date']);
			
			// Check do we have this file already ( useful if you are developer and pucked up attachments DB :D )
			if(is_file($cfg['video_path'].'attach/'.$f.'/'.$n[0])){
print <<<E
<div class="alert alert-info" role="alert"><i class="fa fa-file"></i> Файл найден локально</div>
E;
				$q = $db->query("UPDATE vk_attach SET `path` = '".$cfg['video_path']."attach/".$f."/".$n[0]."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
				
				if($_GET['auto'] == '1'){
					$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'video' AND `is_local` = 0");
					if($nrow['attach_id'] > 0){
						print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_found_local']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atvi&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_found_local']);
					}
				}
			} else {
			
				// Are you reagy kids? YES Capitan Curl!
				require_once(ROOT.'classes/curl.php');
				$c = new cu();
				$c->curl_on();
			
				$out = $c->curl_req(array(
						'uri' => $q['uri'],
						'method'=>'',
						'return'=>1
				));
			
				if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
					$saved = $c->file_save(array('path'=>$cfg['video_path'].'attach/'.$f.'/','name'=>$n[0]),$out['content']);
					if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;
						$q = $db->query("UPDATE vk_attach SET `path` = '".$cfg['video_path']."attach/".$f."/".$n[0]."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
					
						if($_GET['auto'] == '1'){
							$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'video' AND `is_local` = 0");
							if($nrow['attach_id'] > 0){
								print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atvi&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_photo_next_cd']);
							}
						}
						
					} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
					}
				} else {
					// If error, let's try to see wtf is going on
					if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
						$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
						if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
					}
					// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
E;
				}
			} // end of local file check fail
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = ATVI
	
	// Attach - Link
	if($queue_id > 0 && $_GET['t']=='atli' && $queue_oid != 0){
		$don = true;
		// Get photo info
		$q = $db->query_row("SELECT * FROM vk_attach WHERE `attach_id` = {$queue_id} AND `owner_id` = {$queue_oid}");
		if($q['uri'] != ''){
			
			// Get file name
			preg_match("/[^\/]+$/",$q['uri'],$n);
			$f = date("Y-m",$q['date']);
			
			// Check do we have this file already ( useful if you are developer and pucked up attachments DB :D )
			if(is_file($cfg['photo_path'].'attach/'.$f.'/'.$n[0])){
print <<<E
<div class="alert alert-info" role="alert"><i class="fa fa-file"></i> Файл найден локально</div>
E;
				$q = $db->query("UPDATE vk_attach SET `path` = '".$cfg['photo_path']."attach/".$f."/".$n[0]."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
				
				if($_GET['auto'] == '1'){
					$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'link' AND `is_local` = 0");
					if($nrow['attach_id'] > 0){
						print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_found_local']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atli&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_found_local']);
					}
				}
			} else {
			
				// Are you reagy kids? YES Capitan Curl!
				require_once(ROOT.'classes/curl.php');
				$c = new cu();
				$c->curl_on();
			
				$out = $c->curl_req(array(
						'uri' => $q['uri'],
						'method'=>'',
						'return'=>1
				));
			
				if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
					$saved = $c->file_save(array('path'=>$cfg['photo_path'].'attach/'.$f.'/','name'=>$n[0]),$out['content']);
					if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;
						$q = $db->query("UPDATE vk_attach SET `path` = '".$cfg['photo_path']."attach/".$f."/".$n[0]."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
						
						if($_GET['auto'] == '1'){
							$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'link' AND `is_local` = 0");
							if($nrow['attach_id'] > 0){
								print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atli&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_photo_next_cd']);
							}
						}
						
					} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
					}
				} else {
					// If error, let's try to see wtf is going on
					if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
						$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
						if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
					}
					// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
E;
				}
			} // end of local file check fail
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = ATLI
	
	// Attach - Music
	if($queue_id > 0 && $_GET['t']=='atau' && $queue_oid != 0){
		$don = true;
		// Get audio info
		$q = $db->query_row("SELECT * FROM vk_attach WHERE `attach_id` = {$queue_id} AND `owner_id` = {$queue_oid}");
		if($q['uri'] != ''){
			
			// Are you reagy kids? YES Capitan Curl!
			require_once(ROOT.'classes/curl.php');
			$c = new cu();
			$c->curl_on();
			
			// Get file name
			$q['uri'] = preg_replace("/\?extra\=.*/","",$q['uri']);
			preg_match("/[^\.]+$/",$q['uri'],$n);
			if(mb_strlen($q['title']) > 200){ $q['title'] = mb_substr($row['caption'],0,200); }
			$nam = $c->clean_name($q['caption'].' - '.$q['title'].' ['.$q['attach_id'].'].'.$n[0]);
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
			
			// Check do we have this file already ( useful if you are developer and pucked up attachments DB :D )
			if(is_file($cfg['music_path'].'attach/'.$fnam)){
print <<<E
<div class="alert alert-info" role="alert"><i class="fa fa-file"></i> Файл <b>{$nam}</b> найден локально</div>
E;
				
				$q1 = $db->query("UPDATE vk_attach SET `path` = '".$cfg['music_path'].'attach/'.$db->real_escape($cnam)."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
				
				if($_GET['auto'] == '1'){
					$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'audio' AND `uri` != '' AND `is_local` = 0");
					if($nrow['attach_id'] > 0){
						print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_found_local']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atau&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_found_local']);
					}
				}
			} else {
			
				$out = $c->curl_req(array(
						'uri' => $q['uri'],
						'method'=>'',
						'return'=>1
				));

				if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
					$saved = $c->file_save(array('path'=>$cfg['music_path'].'attach/','name'=>$fnam),$out['content']);
					if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл <b>{$nam}</b> сохранен</div>
E;
				
						$q1 = $db->query("UPDATE vk_attach SET `path` = '".$cfg['music_path'].'attach/'.$db->real_escape($cnam)."' WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
						
						if($_GET['auto'] == '1'){
							$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `path` = '' AND `type` = 'audio' AND `uri` != '' AND `is_local` = 0");
							if($nrow['attach_id'] > 0){
								print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_music_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atau&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_music_next_cd']);
							}
						}
					
					} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла {$nam}</div>
E;
					}
				} else {
					// If error, let's try to see wtf is going on
					if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
						$out = $c->curl_req(array('uri' => $q['uri'], 'method'=>'', 'return'=>0 ));
						if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
					}
					// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста для {$nam}{$error_code}</div>
E;
				}
			} // end of local file check fail
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = ATAU
	
	// Attach - Documents
	if($queue_id > 0 && $_GET['t']=='atdc'){
		$don = true;
		// Get document info
		$q = $db->query_row("SELECT * FROM vk_attach WHERE `attach_id` = {$queue_id} AND `owner_id` = {$queue_oid}");
		if($q['link_url'] != ''){
			
			// Are you reagy kids? YES Capitan Curl!
			require_once(ROOT.'classes/curl.php');
			$c = new cu();
			$c->curl_on();
			//preg_match("/[^\/]+$/",$q['uri'],$n);
			$f = date("Y-m",$q['date']);
			//print_r($cfg['docs_path'].$f.'/'.$n[0]);
			$out = $c->curl_req(array(
					'uri' => $q['link_url'],
					'method'=>'',
					'return'=>1
			));
			
			if($out['err'] == 0 && $out['errmsg'] == '' && $out['content'] != '' && substr($out['content'],0,5) != '<html' && substr($out['content'],0,9) != '<!DOCTYPE'){
				$saved = $c->file_save(array('path'=>$cfg['docs_path'].'attach/'.$f.'/','name'=>$q['attach_id'].'.'.$q['caption']),$out['content']);
				if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Файл сохранен</div>
E;

					$prev_q = '';
					if($q['uri'] != ''){
						$out_pre = $c->curl_req(array(
							'uri' => $q['uri'],
							'method'=>'',
							'return'=>1
						));
						if($out_pre['err'] == 0 && $out_pre['errmsg'] == '' && $out_pre['content'] != '' && substr($out_pre['content'],0,5) != '<html' && substr($out_pre['content'],0,9) != '<!DOCTYPE'){
							preg_match("/[^\.]+$/",$q['uri'],$np);
							$saved_pre = $c->file_save(array('path'=>$cfg['docs_path'].'attach/preview/','name'=>$q['attach_id'].'.'.$np['0']),$out_pre['content']);
							if($saved){
print <<<E
<div class="alert alert-success" role="alert"><i class="fa fa-file"></i> Превью сохранено</div>
E;
								$prev_q = ", `path` = '".$cfg['docs_path']."attach/preview/".$q['attach_id'].".".$np[0]."'";
							}
						}
					}

					$q = $db->query("UPDATE vk_attach SET `player` = '".$cfg['docs_path'].'attach/'.$f."/".$q['attach_id'].".".$q['caption']."'".$prev_q." WHERE `attach_id` = ".$queue_id." AND `owner_id` = ".$queue_oid."");
					
					if($_GET['auto'] == '1'){
						$nrow = $db->query_row("SELECT attach_id, owner_id FROM vk_attach WHERE `player` = '' AND `type` = 'doc' AND `is_local` = 0");
						if($nrow['attach_id'] > 0){
							print $skin->reload('info',"Страница будет обновлена через <span id=\"gcd\">".$cfg['sync_docs_next_cd']."</span> сек.",$cfg['vkbk_url']."queue.php?t=atdc&id=".$nrow['attach_id']."&oid=".$nrow['owner_id']."&auto=1",$cfg['sync_docs_next_cd']);
						}
					}
					
				} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Ошибка при сохранении файла</div>
E;
				}
			} else {
				// If error, let's try to see wtf is going on
				if((substr($out['content'],0,5) == '<html') || (substr($out['content'],0,9) == '<!DOCTYPE')){
					$out = $c->curl_req(array('uri' => $q['link_url'], 'method'=>'', 'return'=>0 ));
					if(isset($out['header'])){ $error_code = "<br/>Ответ сервера: {$out['header']['http_code']}"; }
				}
				// Something wrong with response or connection
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Невозможно получить данные с удаленного хоста.{$error_code}</div>
E;
			}
			
		} else {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> ID найден в очереди но ссылка на файл отсутствует.</div>
E;
		}
	} // End of T = ATDC
	
	if($don == false) {
print <<<E
<div class="alert alert-danger" role="alert"><i class="fa fa-warning"></i> Неправильный тип или ID</div>
E;
	}
	
}

print <<<E
            <table class="table table-striped white-box">
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
  <td style="text-align:center;"><a href="queue.php?t=p&id={$row['id']}" style="font-size:130%;" class="label label-success" id="{$row['id']}" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fa fa-arrow-circle-up"></b></a>{$auto}</td>
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
  <td style="text-align:center;"><a href="queue.php?t=v&id={$row['id']}" style="font-size:130%;" class="label label-info" id="{$row['id']}" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fa fa-arrow-circle-up"></b></a>{$auto}</td>
</tr>
E;
	}
}
$first['dc'] = true;
if($bar_queue['dc'] > 0){
	$r = $db->query("SELECT * FROM vk_docs WHERE `in_queue` = 1 ".($skip_list != '' ? "AND `id` NOT IN (".$skip_list.")" : "")." ORDER BY date DESC LIMIT 0,{$show}");
	while($row = $db->return_row($r)){
		$row['date'] = date("Y-m-d H:i:s",$row['date']);
		// Add a autodownload for the first element in list
		if($first['dc'] == true){
			$first['dc'] = false;
			$auto = "&nbsp;&nbsp;<a href=\"queue.php?t=dc&id={$row['id']}&auto=1\" style=\"font-size:130%;\" class=\"label label-info\" onClick=\"jQuery('#{$row['id']}').hide();return true;\" title=\"Скачать автоматически\"><b class=\"fa fa-repeat\"></b></a>";
		} else { $auto = ''; }
print <<<E
<tr>
  <td>{$row['id']}</td>
  <td><a href="{$row['uri']}" target="_blank">{$row['title']}</a></td>
  <td>{$row['date']}</td>
  <td style="text-align:center;"><a href="queue.php?t=dc&id={$row['id']}" style="font-size:130%;" class="label label-info" id="{$row['id']}" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fa fa-arrow-circle-up"></b></a>{$auto}</td>
</tr>
E;
	}
}

// Profiles
$first['pr'] = true;
if($pr['c'] > 0){
	$no_queue = false;
	$r = $db->query("SELECT * FROM vk_profiles WHERE `photo_path` = '' LIMIT 0,{$show}");
	while($row = $db->return_row($r)){
		$row['type'] = 'profiles';
		$row['uri'] = $row['photo_uri'];
		print $skin->queue_list_attach($row,$first['pr']);
		if($first['pr'] == true){ $first['pr'] = false; }
	}
}

// Groups
$first['gr'] = true;
if($gr['c'] > 0){
	$no_queue = false;
	$r = $db->query("SELECT * FROM vk_groups WHERE `photo_path` = '' LIMIT 0,{$show}");
	while($row = $db->return_row($r)){
		$row['type'] = 'groups';
		$row['uri'] = $row['photo_uri'];
		print $skin->queue_list_attach($row,$first['gr']);
		if($first['gr'] == true){ $first['gr'] = false; }
	}
}

// Attach - Photo & Video (preview)
$first['atph'] = true;
$first['atvi'] = true;
$first['atli'] = true;
$first['atau'] = true;
$first['atdc'] = true;
$r = $db->query("SELECT * FROM vk_attach WHERE `path` = '' AND `uri` != '' AND `is_local` = 0 LIMIT 0,{$show}");
while($row = $db->return_row($r)){
	$no_queue = false;
	if($row['type'] == 'photo'){
		print $skin->queue_list_attach($row,$first['atph']);
		if($first['atph'] == true){ $first['atph'] = false; }
	}
	if($row['type'] == 'video'){
		print $skin->queue_list_attach($row,$first['atvi']);
		if($first['atvi'] == true){ $first['atvi'] = false; }
	}
	if($row['type'] == 'link'){
		print $skin->queue_list_attach($row,$first['atli']);
		if($first['atli'] == true){ $first['atli'] = false; }
	}
	if($row['type'] == 'audio'){
		print $skin->queue_list_attach($row,$first['atau']);
		if($first['atau'] == true){ $first['atau'] = false; }
	}
	if($row['type'] == 'doc'){
		print $skin->queue_list_attach($row,$first['atdc']);
		if($first['atdc'] == true){ $first['atdc'] = false; }
	}
}

if($all_queue == 0 && $no_queue == true) {
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

if(!$cfg['pj']){
	print $skin->footer(array('extend'=>''));
}

$db->close($res);

?>