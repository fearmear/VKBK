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

$do = (isset($_GET['do'])) ? $_GET['do'] : '';

print <<<E
<div class="container">
          <h2 class="sub-header">Синхронизация</h2>
          <div class="table-responsive white-box">
            <table class="table table-striped">
E;

if($do != ''){

$don = false;

// Include VK.API
require_once(ROOT.'classes/VK/VK.php');

// Check token
$q = $db->query("SELECT * FROM vk_session WHERE `vk_id` = 1");
$vk_session = $row = $db->return_row($q);
$token_valid = false;

if($vk_session['vk_token']){
	$vk = new VK($cfg['vk_id'], $cfg['vk_secret'], $vk_session['vk_token']);
	$token_valid = $vk->checkAccessToken($vk_session['vk_token']);
} else {
	$vk = new VK($cfg['vk_id'], $cfg['vk_secret']);
}
// Set API version
$vk->setApiVersion(5.45);

if($vk_session['vk_token'] != '' && $token_valid == true){
	try {

		// Albums sync
		if($do == 'albums'){
			$don = true;
			$to_delete = false;
			$album_list = array();
			$album_vk = array();
			$album_delete = '';
			$album_create = array();
			$album_renamed = array('count'=>0,'list'=>'');
		
			// Get local albums
			$r = $db->query("SELECT id, name FROM vk_albums WHERE id > -9000");
			while($row = $db->return_row($r)){
				$album_list['ids'][] = $row['id'];
				$album_list['names'][$row['id']] = $row['name'];
			}
			
			$local_albums = sizeof($album_list['ids']);
		
			// Get VK albums
			$api = $vk->api('photos.getAlbums', array(
				'owner_id' => $vk_session['vk_user'],
				'need_system' => 1
			));
			
			$album_vk = $api['response']['items'];
			
			// Check local albums for IDs and delete unknown
			if(!empty($album_vk[0]['id']) && !empty($local_albums)){
				foreach($album_vk as $k => $v){
					
					// Если альбом есть в базе
					if(in_array($v['id'],$album_list['ids'])){
						// Если альбом есть локально, то убираем его из локального списка
						// Оставшиеся альбомы пойдут на удаление
						$key = array_search($v['id'], $album_list['ids']);
						unset($album_list['ids'][$key]);
						$to_delete = true;
						
						// Проверяем изменилось ли название альбома
						if($v['title'] != $album_list['names'][$v['id']]){
							$q = $db->query("UPDATE vk_albums SET name = '".$v['title']."' WHERE id = ".$v['id']);
							$album_renamed['count']++;
							$album_renamed['list'] .= '&laquo;'.$album_list['names'][$v['id']].'&raquo; > &laquo;'.$v['title'].'&raquo;<br/>';
						}
						
					} else {
						// Если альбом не найден локально, добавляем его в список импорта
						$album_create[] = $v;
					}
				}
			} else if(!empty($album_vk[0]['id']) && empty($local_albums)) {
				foreach($album_vk as $k => $v){
					$album_create[] = $v;
				}
			}

			if($album_renamed['count'] > 0){
				print '<tr><td>Альбомов переименовано: <b>'.$album_renamed['count'].'</b><br/>'.$album_renamed['list'].'</td></tr>';
			}

			// Clean unused\deleted albums
			print '<tr><td>Альбомов на удаление: <b>'.sizeof($album_list['ids']).'</b></td></tr>';
			if(!empty($album_list['ids']) && $to_delete == true){
				$album_delete = implode(',',$album_list['ids']);
				if($album_delete != ''){
					$q = $db->query("DELETE FROM vk_albums WHERE `id` IN({$album_delete})");
				}
			}
		
			// Update untouched albums
			$q = $db->query("UPDATE vk_albums SET updated = ".time()." WHERE id > -9000");
		
			// Import new albums
			print '<tr><td>Альбомов на создание: <b>'.sizeof($album_create).'</b></td></tr>';
			if(!empty($album_create)){
				$album_new = '';
				foreach($album_create as $k => $v){
					$album_new .= (($album_new!='') ? ',' : '').
					"({$v['id']},'{$v['title']}',".time().",".time().",{$v['size']},0)";
				}
				$q = $db->query("INSERT INTO vk_albums (`id`,`name`,`created`,`updated`,`img_total`,`img_done`) VALUES ".$album_new."");
			}
		
			// Fouth - update albums count
			$q = $db->query("UPDATE vk_counters SET `album` = (SELECT COUNT(*) FROM vk_albums WHERE id > -9000)");
		
			print '<tr><td><div class="alert alert-success" role="alert"><strong>УРА!</strong> Синхронизация завершена. Перейти в <a href="albums.php">альбомы</a> или <a href="sync.php?do=photo">синхронизировать</a> фотографии</div></td></tr>';

		} // DO Albums end
	

		// Photos sync
		if($do == 'photo'){
			$don = true;
	
			// Check do we have album ID in GET
			$album_id = (isset($_GET['album'])) ? intval($_GET['album']) : '';
			$album_total = (isset($_GET['at'])) ? intval($_GET['at']) : 0;
			$album_process = (isset($_GET['ap'])) ? intval($_GET['ap']) : 0;
			
			if($album_total > 0 && $album_process <= $album_total){
				$per = $album_total/100;
				$done['al'] = ceil($album_process / $per);
				// Make a progress bar
print <<<E
<div class="row" style="margin:0;">
<div class="col-sm-12">
<div class="progress" style="margin-top:20px;">
	<div class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="{$done['al']}" aria-valuemin="0" aria-valuemax="100" style="width: {$done['al']}%"><span class="sr-only">{$done['al']}% Complete</span></div>
</div>
</div>
</div>
E;
			}

			// No album? Let's start from the beginning
			if($album_id == ''){
				// Clean DB log before write something new
				$q4 = $db->query("UPDATE vk_status SET `val` = '' WHERE `key` = 'log_photo'");

				$log = array();

				// Set all local photos to album -9000
				$q = $db->query("UPDATE vk_photos SET `album_id` = -9000");
				$moved = $db->affected_rows();
				array_unshift($log,"<tr><td>Перемещаю фотографии в системный альбом. Всего - <b>".$moved."</b></td></tr>");
				print $log[0];
				unset($moved);

				// Save log
				array_unshift($log,"<tr><td>Начинаю синхронизацию фотографий...</td></tr>");
				print $log[0];
			
				$q = $db->query("UPDATE vk_status SET `val` = CONCAT('".implode("\r\n",$log)."',`val`) WHERE `key` = 'log_photo'");
				// Get first album ID
				$row = $db->query_row("SELECT id FROM vk_albums WHERE id > -9000 LIMIT 1");
				// Get albums count
				$alb_c = $db->query_row("SELECT COUNT(*) as count FROM vk_albums WHERE id > -9000");
				$album_total = $alb_c['count'];
				// Reload page
				print $skin->reload('warning',"<b>Пристегнитесь!</b> Начинаю синхронизацию фотографий через  <span id=\"gcd\">".$cfg['sync_photo_start_cd']."</span> сек...",$cfg['vkbk_url']."sync.php?do=photo&album=".$row['id']."&offset=0&at=".$album_total."&ap=1",$cfg['sync_photo_start_cd']);
			} // if album is not found

			// Album ID found
			if($album_id != ''){
				// Logging
				$log = array();
				$album_name = $album_id;
			
				// Get album name
				$nrow = $db->query_row("SELECT name FROM vk_albums WHERE `id` = ".$album_id."");
				if($nrow['name'] != ''){
					$album_name = $nrow['name'];
				}
			
				array_unshift($log,"<tr><td>Начинаю синхронизацию альбома <b>".$album_name."</b></td></tr>");
				print $log[0];
			
				$photos_vk_total = 0;
			
				$alb = $album_id;
				if($alb == -15){ $alb = 'saved'; }
				if($alb == -7){ $alb = 'wall'; }
				if($alb == -6){ $alb = 'profile'; }
				$count = 1000;
				$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
			
				// We logged in, get VK photos
				$api = $vk->api('photos.get', array(
					'owner_id' => $vk_session['vk_user'],
					'album_id' => $alb,
					'rev' => 1, // порядок сортировки фотографий (1 — антихронологический, 0 — хронологический)
					'extended' => 0, // 1 — будут возвращены дополнительные поля likes, comments, tags, can_comment, reposts. Поля comments и tags содержат только количество объектов.
					'photo_sizes' => 0, // параметр, указывающий нужно ли возвращать ли доступные размеры фотографии в специальном формате. 
					'offset' => $offset,
					'count' => $count
				));
				
				$photos_vk = $api['response']['items'];
				$photos_vk_total = $api['response']['count'];
			
				$photos_vk_list = array();
				// Get VK IDs
				foreach($photos_vk as $k => $v){
					$photos_vk_list[] = $v['id'];
				}
				
				// I want this logic in one line, but this blow my mind so...
				$to = 0;
				if($offset == 0){
					$to = $count;
					if($count > $photos_vk_total){
						$to = $photos_vk_total;
					}
				} else {
					if(($count+$offset) > $photos_vk_total){
						$to = $photos_vk_total;
					} else {
						$to = $count+$offset;
					}
				}
				if($offset > 0){ $ot = $offset; } else { $ot = 1; }
				
				array_unshift($log,'<tr><td>Получаем фото <b> '.$ot.' - '.$to.' / '.$photos_vk_total.'</b> из ВК.</td></tr>');
				print $log[0];
			
				// No photos in list? Probably a bad response. Refresh...
				//if(sizeof($photos_vk_list) < 1){
					//print $skin->reload('warning',"Страница будет обновлена через ".$cfg['sync_photo_error_cd']." сек.",$cfg['vkbk_url']."sync.php?do=photo&album=".$album_id."&offset=".$offset,$cfg['sync_photo_error_cd']);
				//}
			
				$photos_list = array();
				// No photos in list? Probably album is empty.
				if(sizeof($photos_vk_list) > 0){
					// get local IDs
					$q = $db->query("SELECT id FROM vk_photos WHERE `id` IN(".implode(',',$photos_vk_list).")");
					while($row = $db->return_row($q)){
						$photos_list[] = $row['id'];
						//print_r($row);
					}
				}
			
				// Get list of IDs which is NOT in local DB. so they are NEW
				// Compare VK IDs with local IDs
				$photos_create = array_diff($photos_vk_list,$photos_list);
			
				if(sizeof($photos_list) > 0){
					// Update album for local IDs which was found
					$q = $db->query("UPDATE vk_photos SET `album_id` = ".$album_id." WHERE `id` IN(".implode(',',$photos_list).") ");
					$moved = $db->affected_rows();
					array_unshift($log,'<tr><td>Найденные локально фото перемещены обратно в альбом. Всего - <b>'.$moved.'</b></td></tr>');
					print $log[0];
					unset($moved);
				}
			
				// Put new photos to queue
				$photos_data = array();
				
				foreach($photos_vk as $k => $v){
					if(in_array($v['id'],$photos_create)){
						// Get biggest photo
						if(isset($v['photo_2560'])){ $v['uri'] = $v['photo_2560']; }
							elseif(isset($v['photo_1280'])){ $v['uri'] = $v['photo_1280'];}
								elseif(isset($v['photo_807'])){ $v['uri'] = $v['photo_807'];}
									elseif(isset($v['photo_604'])){ $v['uri'] = $v['photo_604'];}
										elseif(isset($v['photo_130'])){ $v['uri'] = $v['photo_130'];}
											elseif(isset($v['photo_75'])){ $v['uri'] = $v['photo_75'];}
				
						
						$photos_data[$v['id']] = array(
							'album_id' => $v['album_id'],
							'width' => (!is_numeric($v['width']) ? 0 : $v['width']),
							'height' => (!is_numeric($v['height']) ? 0 : $v['height']),
							'date' => $v['date'],
							'uri' => $v['uri']
						);
					}
				} // foreach end
				
				if(!empty($photos_data) && (sizeof($photos_create) == sizeof($photos_data))){
					$data_sql = array(0=>'');
					$data_limit = 250;
					$data_i = 1;
					$data_k = 0;
					foreach($photos_data as $k => $v){
						$data_sql[$data_k] .= ($data_sql[$data_k] != '' ? ',' : '')."({$k},{$v['album_id']},{$v['date']},'{$v['uri']}',{$v['width']},{$v['height']},0,0,'','',true)";
						$data_i++;
						if($data_i > $data_limit){
							$data_i = 1;
							$data_k++;
						}
					}
					
					foreach($data_sql as $k => $v){
						$q = $db->query("INSERT INTO vk_photos (`id`,`album_id`,`date_added`,`uri`,`width`,`height`,`date_done`,`saved`,`path`,`hash`,`in_queue`) VALUES {$v}");
					}

					array_unshift($log,'<tr><td>Новые фото добавлены в очередь. Всего - <b>'.sizeof($photos_create).'</b></td></tr>');
					print $log[0];
				}

				// Offset done
				// Now check DO we need an another run for this album
				// Or we can go to the next album

				// If we done with all photos in this album
				if(($offset+$count) >= $photos_vk_total){
					array_unshift($log,'<tr><td>Обработка фото в альбоме завершена. Синхронизирую следующий альбом...</td></tr>');
					print $log[0];
				
					// Save log to the DB
					$q = $db->query("UPDATE vk_status SET `val` = CONCAT('".implode("\r\n",$log)."',`val`) WHERE `key` = 'log_photo'");
				
					// Get NEXT album id
					$row = $db->query_row("SELECT id FROM vk_albums WHERE id > ".$album_id." LIMIT 1");
					if(!empty($row['id']) && $row['id'] > $album_id){
						$album_next = $row['id'];
						$album_process++;
						// Got next album, let's reload page
						print $skin->reload('info',"Страница будет обновлена через  <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."sync.php?do=photo&album=".$album_next."&offset=0&at=".$album_total."&ap=".$album_process."",$cfg['sync_photo_next_cd']);
					} else {
						// No unsynced photos left and all abums was synced too. This is the end...
						// Let's make recount photos
						$total = array('albums'=>0,'photos'=>0);
						$q = $db->query("SELECT id FROM vk_albums WHERE id > -9000");
						while($row = $db->return_row($q)){
							$total['albums']++;
							$q2 = $db->query_row("SELECT COUNT(id) as photos FROM vk_photos WHERE `album_id` = ".$row['id']."");
							$total['photos'] += $q2['photos'];
							$q3 = $db->query("UPDATE vk_albums SET `img_total` = ".$q2['photos'].", `img_done` = ".$q2['photos']." WHERE `id` = ".$row['id']."");
							unset($q2);
						}
						
						// Update counters
						$q5 = $db->query("UPDATE vk_counters SET `photo` = (SELECT COUNT(*) FROM vk_photos)");
					
						array_unshift($log,'<tr><td><div class="alert alert-success" role="alert"><strong>УРА!</strong> Синхронизация всех фотографий завершена.<br/>Альбомов - <b>'.$total['albums'].'</b>, фотографий - <b>'.$total['photos'].'</b></div></td></tr>');
						print $log[0];
						
						// Get Settings (Auto-Queue)
						$aq = $db->query_row("SELECT val as auto FROM vk_status WHERE `key` = 'auto-queue-photo'");
						if($aq['auto'] == 1){
							print $skin->reload('info',"Переходим к очереди закачки через  <span id=\"gcd\">".$cfg['sync_photo_auto_cd']."</span> сек.",$cfg['vkbk_url']."queue.php",$cfg['sync_photo_auto_cd']);
						}
						
					}

				} else {
					// Some photos in this album is not synced yed
					array_unshift($log,'<tr><td>Перехожу к следующей порции фотографий в данном альбоме...</td></tr>');
					print $log[0];
				
					// Save log to the DB
					$q = $db->query("UPDATE vk_status SET `val` = CONCAT('".implode("\r\n",$log)."',`val`) WHERE `key` = 'log_photo'");
				
					// Calculate offset and reload page
					$offset_new = $offset+$count;
					print $skin->reload('info',"Страница будет обновлена через  <span id=\"gcd\">".$cfg['sync_photo_next_cd']."</span> сек.",$cfg['vkbk_url']."sync.php?do=photo&album=".$album_id."&offset=".$offset_new."&at=".$album_total."&ap=".$album_process."",$cfg['sync_photo_next_cd']);
				}
			
			
				// Get log if any process rinning
				$old_log = $db->query_row("SELECT val as p FROM vk_status WHERE `key` = 'log_photo'");
				//print_r($old_log);
				if($old_log['p'] != ''){
					print '<tr><td><h4>Лог</h4></td></tr>'.$old_log['p'];
				}

			} // end if album

		} // DO Photos end
		
		
		// Music sync
		if($do == 'music'){
			$don = true;
			
			// Check do we have music PART in GET
			$part = (isset($_GET['part'])) ? intval($_GET['part']) : '';

			// No album? Let's start from the beginning
			if($part == ''){
				
				// Clean DB log before write something new
				$q4 = $db->query("UPDATE vk_status SET `val` = '' WHERE `key` = 'log_music'");

				$log = array();
				
				// Set all saved local music to `deleted` state
				$q = $db->query("UPDATE vk_music SET `deleted` = 1 WHERE `saved` = 1");
				
				// Save log
				array_unshift($log,"<tr><td>Начинаю синхронизацию...</td></tr>");
				print $log[0];
			
				$q = $db->query("UPDATE vk_status SET `val` = CONCAT('".implode("\r\n",$log)."',`val`) WHERE `key` = 'log_music'");
				
				// Reload page
				print $skin->reload('warning',"<b>Увертюра!</b> Начинаю синхронизацию музыки через  <span id=\"gcd\">".$cfg['sync_music_start_cd']."</span> сек...",$cfg['vkbk_url']."sync.php?do=music&part=1",$cfg['sync_music_start_cd']);
				
			} // if music part is not found
			
			// Music PART found
			if($part >= 1){
				
				// Logging
				$log = array();
				
				array_unshift($log,"<tr><td>Синхронизация музыки начата.</td></tr>");
				print $log[0];
			
				$music_vk_total = 0;
				$count = 500;
				$offset = ($part-1)*$count;
				
				// We logged in, get VK music
				$api = $vk->api('audio.get', array(
					'owner_id' => $vk_session['vk_user'],
					'need_user' => 0, // возвращать информацию о пользователях, загрузивших аудиозапись
					'offset' => $offset,
					'count' => $count
				));
				
				$music_vk = $api['response']['items'];
				$music_vk_total = $api['response']['count'];
				
				$music_vk_list = array();
				// Get VK IDs
				foreach($music_vk as $k => $v){
					$music_vk_list[] = $v['id'];
				}
				
				// I want this logic in one line, but this blow my mind so...
				$to = 0;
				if($offset == 0){
					$to = $count;
					if($count > $music_vk_total){
						$to = $music_vk_total;
					}
				} else {
					if(($count+$offset) > $music_vk_total){
						$to = $music_vk_total;
					} else {
						$to = $count+$offset;
					}
				}
				if($offset > 0){ $ot = $offset; } else { $ot = 1; }
				
				array_unshift($log,'<tr><td>Получаем аудиозаписи <b> '.$ot.' - '.$to.' / '.$music_vk_total.'</b> из ВК.</td></tr>');
				print $log[0];
				
				$music_list = array();
				// get local IDs
				$q = $db->query("SELECT id FROM vk_music WHERE `id` IN(".implode(',',$music_vk_list).")");
				while($row = $db->return_row($q)){
					$music_list[] = $row['id'];
				}
			
				// Get list of IDs which is NOT in local DB. so they are NEW
				// Compare VK IDs with local IDs
				$music_create = array_diff($music_vk_list,$music_list);
			
				if(sizeof($music_list) > 0){
					// Update status for local IDs which was found
					$q = $db->query("UPDATE vk_music SET `saved` = 1, `deleted` = 0 WHERE `id` IN(".implode(',',$music_list).") AND `in_queue` = 0");
					$moved = $db->affected_rows();
					array_unshift($log,'<tr><td>Пропускаем найденные локально аудиозаписи. Всего - <b>'.$moved.'</b></td></tr>');
					print $log[0];
					unset($moved);
				}
				
				// Put new music to queue
				$music_data = array();
				
				foreach($music_vk as $k => $v){
					if(in_array($v['id'],$music_create)){
						
						$music_data[$v['id']] = array(
							'artist' => ($v['artist'] == '' ? '- Unknown -' : $v['artist']),
							'title' => ($v['title'] == '' ? '- Unknown -' : $v['title']),
							'duration' => (!is_numeric($v['duration']) ? 0 : $v['duration']),
							'date' => $v['date'],
							'uri' => $v['url']
						);
					}
				} // foreach end
				
				if(!empty($music_data) && (sizeof($music_create) == sizeof($music_data))){
					$data_sql = array(0=>'');
					$data_limit = 250;
					$data_i = 1;
					$data_k = 0;
					foreach($music_data as $k => $v){
						$data_sql[$data_k] .= ($data_sql[$data_k] != '' ? ',' : '')."({$k},'".mysql_real_escape_string($v['artist'])."','".mysql_real_escape_string($v['title'])."',{$v['duration']},'{$v['uri']}',{$v['date']},0,0,0,'','',true)";
						$data_i++;
						if($data_i > $data_limit){
							$data_i = 1;
							$data_k++;
						}
					}
					//print_r($data_sql);
					foreach($data_sql as $k => $v){
						$q = $db->query("INSERT INTO vk_music (`id`,`artist`,`title`,`duration`,`uri`,`date_added`,`date_done`,`saved`,`deleted`,`path`,`hash`,`in_queue`) VALUES {$v}");
					}

					array_unshift($log,'<tr><td>Новые аудиозаписи добавлены в очередь. Всего - <b>'.sizeof($music_create).'</b></td></tr>');
					print $log[0];
				}

				// Offset done
				// Now check DO we need an another run
				// Or we done

				// If we done with all music
				if(($offset+$count) >= $music_vk_total){

						// No unsynced music left. This is the end...
						// Let's make recount music
						$total = array('music'=>0,'deleted'=>0);

							$q1 = $db->query_row("SELECT COUNT(id) as m FROM vk_music WHERE `saved` = 1 OR `in_queue` = 1");
							$total['music'] = $q1['m'];
							
							$q2 = $db->query_row("SELECT COUNT(id) as m FROM vk_music WHERE `deleted` = 1");
							$total['deleted'] = $q2['m'];
							
						
						// Update counters
						$q5 = $db->query("UPDATE vk_counters SET `music` = (SELECT COUNT(*) FROM vk_music WHERE `saved` = 1 OR `in_queue` = 1)");
					
						array_unshift($log,'<tr><td><div class="alert alert-success" role="alert"><strong>УРА!</strong> Синхронизация всех аудиозаписей завершена.<br/>Треков - <b>'.$total['music'].'</b>, на удаление - <b>'.$total['deleted'].'</b></div></td></tr>');
						print $log[0];
						
						// Get Settings (Auto-Queue)
						$aq = $db->query_row("SELECT val as auto FROM vk_status WHERE `key` = 'auto-queue-audio'");
						if($aq['auto'] == 1){
							print $skin->reload('info',"Переходим к очереди закачки через  <span id=\"gcd\">".$cfg['sync_music_auto_cd']."</span> сек.",$cfg['vkbk_url']."queue.php",$cfg['sync_music_auto_cd']);
						}

				} else {
					// Some photos in this album is not synced yed
					array_unshift($log,'<tr><td>Перехожу к следующей порции аудиозаписей...</td></tr>');
					print $log[0];
				
					// Save log to the DB
					$q = $db->query("UPDATE vk_status SET `val` = CONCAT('".implode("\r\n",$log)."',`val`) WHERE `key` = 'log_music'");
				
					// Calculate offset and reload page
					$part_new = $part+1;
					//print_r($part_new);
					print $skin->reload('info',"Страница будет обновлена через  <span id=\"gcd\">".$cfg['sync_music_next_cd']."</span> сек.",$cfg['vkbk_url']."sync.php?do=music&part=".$part_new."",$cfg['sync_music_next_cd']);
				}
			
				// Get log if any process rinning
				$old_log = $db->query_row("SELECT val as p FROM vk_status WHERE `key` = 'log_music'");
				//print_r($old_log);
				if($old_log['p'] != ''){
					print '<tr><td><h4>Лог</h4></td></tr>'.$old_log['p'];
				}
				
			} // end if part
			
		} // DO Music end
		
		
		// Video sync
		if($do == 'video'){
			$don = true;
			
			// Check do we have video PART in GET
			$part = (isset($_GET['part'])) ? intval($_GET['part']) : '';

			// No album? Let's start from the beginning
			if($part == ''){
				
				// Clean DB log before write something new
				$q4 = $db->query("UPDATE vk_status SET `val` = '' WHERE `key` = 'log_video'");

				$log = array();
				
				// Set all videos to `deleted` state
				$q = $db->query("UPDATE vk_videos SET `deleted` = 1 WHERE `in_queue` = 0");
				
				// Save log
				array_unshift($log,"<tr><td>Начинаю синхронизацию...</td></tr>");
				print $log[0];
			
				$q = $db->query("UPDATE vk_status SET `val` = CONCAT('".implode("\r\n",$log)."',`val`) WHERE `key` = 'log_video'");
				
				// Reload page
				print $skin->reload('warning',"<b>Свет, камера, мотор!</b> Начинаю синхронизацию видеозаписей через  <span id=\"gcd\">".$cfg['sync_video_start_cd']."</span> сек...",$cfg['vkbk_url']."sync.php?do=video&part=1",$cfg['sync_video_start_cd']);
				
			} // if video part is not found
			
			// Video PART found
			if($part >= 1){
				
				// Logging
				$log = array();
				
				array_unshift($log,"<tr><td>Синхронизация видеозаписей начата.</td></tr>");
				print $log[0];
			
				$video_vk_total = 0;
				$count = 200;
				$offset = ($part-1)*$count;
				
				// We logged in, get VK videos
				$api = $vk->api('video.get', array(
					'owner_id' => $vk_session['vk_user'],
					'extended' => 0, // возвращать ли информацию о настройках приватности видео для текущего пользователя
					'offset' => $offset,
					'count' => $count
				));
				
				$video_vk = $api['response']['items'];
				$video_vk_total = $api['response']['count'];
				
				$video_vk_list = array();
				// Get VK IDs
				foreach($video_vk as $k => $v){
					$video_vk_list[] = $v['id'];
				}
				
				// I want this logic in one line, but this blow my mind so...
				$to = 0;
				if($offset == 0){
					$to = $count;
					if($count > $video_vk_total){
						$to = $video_vk_total;
					}
				} else {
					if(($count+$offset) > $video_vk_total){
						$to = $video_vk_total;
					} else {
						$to = $count+$offset;
					}
				}
				if($offset > 0){ $ot = $offset; } else { $ot = 1; }
				
				array_unshift($log,'<tr><td>Получаем видеозаписи <b> '.$ot.' - '.$to.' / '.$video_vk_total.'</b> из ВК.</td></tr>');
				print $log[0];
				
				$video_list = array();
				// get local IDs
				$q = $db->query("SELECT id FROM vk_videos WHERE `id` IN(".implode(',',$video_vk_list).")");
				while($row = $db->return_row($q)){
					$video_list[] = $row['id'];
				}
			
				// Get list of IDs which is NOT in local DB. so they are NEW
				// Compare VK IDs with local IDs
				$video_create = array_diff($video_vk_list,$video_list);
			
				if(sizeof($video_list) > 0){
					// Update status for local IDs which was found
					$q = $db->query("UPDATE vk_videos SET `deleted` = 0 WHERE `id` IN(".implode(',',$video_list).") AND `in_queue` = 0");
					$moved = $db->affected_rows();
					array_unshift($log,'<tr><td>Пропускаем сохраненные ранее видеозаписи. Всего - <b>'.$moved.'</b></td></tr>');
					print $log[0];
					unset($moved);
				}
				
				// Put new video to queue
				$video_data = array();
				
				foreach($video_vk as $k => $v){
					if(in_array($v['id'],$video_create)){
						// Get biggest preview
						if(isset($v['photo_640'])){ $v['uri'] = $v['photo_640'];}
							elseif(isset($v['photo_320'])){ $v['uri'] = $v['photo_320'];}
								elseif(isset($v['photo_130'])){ $v['uri'] = $v['photo_130'];}
						
						$video_data[$v['id']] = array(
							'title' => ($v['title'] == '' ? '- Unknown '.$v['id'].' -' : $v['title']),
							'desc' => ($v['description'] == '' ? '' : $v['description']),
							'duration' => (!is_numeric($v['duration']) ? 0 : $v['duration']),
							'preview_uri' => $v['uri'],
							'date' => $v['adding_date'],
							'player_uri' => $v['player'],
							'access_key' => $v['access_key']
						);
					}
				} // foreach end
				
				if(!empty($video_data) && (sizeof($video_create) == sizeof($video_data))){
					$data_sql = array(0=>'');
					$data_limit = 250;
					$data_i = 1;
					$data_k = 0;
					foreach($video_data as $k => $v){
						$data_sql[$data_k] .= ($data_sql[$data_k] != '' ? ',' : '')."({$k},'".mysql_real_escape_string($v['title'])."','".mysql_real_escape_string($v['desc'])."',{$v['duration']},'{$v['preview_uri']}','','{$v['player_uri']}','{$v['access_key']}',{$v['date']},0,0,true)";
						$data_i++;
						if($data_i > $data_limit){
							$data_i = 1;
							$data_k++;
						}
					}
					
					foreach($data_sql as $k => $v){
						$q = $db->query("INSERT INTO vk_videos (`id`,`title`,`desc`,`duration`,`preview_uri`,`preview_path`,`player_uri`,`access_key`,`date_added`,`date_done`,`deleted`,`in_queue`) VALUES {$v}");
					}

					array_unshift($log,'<tr><td>Новые видеозаписи добавлены в очередь. Всего - <b>'.sizeof($video_create).'</b></td></tr>');
					print $log[0];
				}

				// Offset done
				// Now check DO we need an another run
				// Or we done

				// If we done with all videos
				if(($offset+$count) >= $video_vk_total){

						// No unsynced music left. This is the end...
						// Let's make recount videos
						$total = array('video'=>0,'deleted'=>0);

							$q1 = $db->query_row("SELECT COUNT(id) as v FROM vk_videos WHERE `deleted` = 0");
							$total['video'] = $q1['v'];
							
							$q2 = $db->query_row("SELECT COUNT(id) as v FROM vk_videos WHERE `deleted` = 1");
							$total['deleted'] = $q2['v'];
							
						
						// Update counters
						$q5 = $db->query("UPDATE vk_counters SET `video` = (SELECT COUNT(*) FROM vk_videos WHERE `deleted` = 0)");
					
						array_unshift($log,'<tr><td><div class="alert alert-success" role="alert"><strong>Снято!</strong> Синхронизация всех видеозаписей завершена.<br/>Видео - <b>'.$total['video'].'</b>, на удаление - <b>'.$total['deleted'].'</b></div></td></tr>');
						print $log[0];

				} else {
					// Some photos in this album is not synced yed
					array_unshift($log,'<tr><td>Перехожу к следующей порции видеозаписей...</td></tr>');
					print $log[0];
				
					// Save log to the DB
					$q = $db->query("UPDATE vk_status SET `val` = CONCAT('".implode("\r\n",$log)."',`val`) WHERE `key` = 'log_video'");
				
					// Calculate offset and reload page
					$part_new = $part+1;
					print $skin->reload('info',"Страница будет обновлена через  <span id=\"gcd\">".$cfg['sync_video_next_cd']."</span> сек.",$cfg['vkbk_url']."sync.php?do=video&part=".$part_new."",$cfg['sync_video_next_cd']);
				}
			
				// Get log if any process rinning
				$old_log = $db->query_row("SELECT val as p FROM vk_status WHERE `key` = 'log_video'");
				if($old_log['p'] != ''){
					print '<tr><td><h4>Лог</h4></td></tr>'.$old_log['p'];
				}
				
			} // end if part
			
		} // DO Video end

	// END Of catch :: All DO methods should be INSIDE

	} catch (Exception $error) {
		echo '<tr><td>'.$error->getMessage().'</td></tr>';
	}
// end of Token Check
} else {
	// Token is NOT valid, re-auth?
print <<<E
<tr>
  <td>
    <div class="alert alert-danger" role="alert"><span>Внимание!</span> Токен является недействительным. Необходимо авторизироваться. Перейти в <a href="index.php">Панель управления</a> для авторизации?</div>
  </td>
</tr>
E;
}

if($don == false && $token_valid == true){
print <<<E
<tr>
  <td>
    <div class="alert alert-info" role="alert">Нет заданий для синхронизации</div>
  </td>
</tr>
E;
}

// End of IF DO
} else {
print <<<E
<tr>
  <td>
    <div class="alert alert-info" role="alert">Нет заданий для синхронизации</div>
  </td>
</tr>
E;
}

print <<<E
            </table>
          </div>
</div>
E;

print $skin->footer(array('v'=>$version,'extend'=>''));

$db->close($res);

?>