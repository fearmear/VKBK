<?php

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

define('SYNC_MSG_DEBUG', false);

// Check do we have all needed GET data
$do = false;
$do_opts = array('dlg','msg','next');
$offset = false;
$dlg_id = 0;
$dlg_date = 0;
if(isset($_GET['offset']) && is_numeric($_GET['offset'])){
	$offset = $_GET['offset'] >= 0 ? intval($_GET['offset']) : 0;
}
if(isset($_GET['do']) && in_array($_GET['do'],$do_opts)){
	$do = $_GET['do'];
}
if(isset($_GET['dlg_id']) && is_numeric($_GET['dlg_id'])){
	$dlg_id = intval($_GET['dlg_id']);
}
if(isset($_GET['dlg_date']) && is_numeric($_GET['dlg_date'])){
	$dlg_date = $_GET['dlg_date'] >= 0 ? intval($_GET['dlg_date']) : 0;
}

if($offset === false || $do === false){
	die();
}

require_once('../cfg.php');

// Get DB
require_once(ROOT.'classes/db.php');
$db = new db();
$res = $db->connect($cfg['host'],$cfg['user'],$cfg['pass'],$cfg['base']);

// Get Functions
require_once(ROOT.'classes/func.php');
$f = new func();

$don = false;

// Output JSON container
$output = array(
	'response' => array(
		'error_msg' => '',
		'msg' => array(),
		'next_uri' => '',
		'done' => 0,
		'total' => 0
	),
	'error' => false
);

// Include VK.API
require_once(ROOT.'classes/VK/VK.php');

// Check token
$q = $db->query("SELECT * FROM vk_session WHERE `vk_id` = 1");
$vk_session = $row = $db->return_row($q);
$token_valid = false;

if($vk_session['vk_token']){
	$vk = new VK($cfg['vk_id'], $cfg['vk_secret'], $vk_session['vk_token']);
	// Set API version
	$vk->setApiVersion($cfg['vk_api_version']);
	$token_valid = $vk->checkAccessToken($vk_session['vk_token']);
} else {
	$vk = new VK($cfg['vk_id'], $cfg['vk_secret']);
	// Set API version
	$vk->setApiVersion($cfg['vk_api_version']);
}

if($vk_session['vk_token'] != '' && $token_valid == true){
	try {
	
	// Do = Dialogs
	if($do == 'dlg'){
		$count = 100; // Maximum: 100
		$output['response']['done'] = $count;
		
	// We logged in, get VK dialog list
	$api = $vk->api('messages.getConversations', array(
		'offset' => $offset,
		'count' => $count
	));
	
	$api_dialogs = array();
	$vk_dialogs_total = 0;
	
	if($api['response'] != ''){
		
		$don = true;
		$api_dialogs = $api['response']['items'];
		$vk_dialogs_total = $api['response']['count'];
		
	}
	
	// Check & process
	if(!empty($api_dialogs)){
		
		$dialog_ids = '';
		$dialog_new_ids = array();
		$dialog_group_ids = '';
		$dialog_new_group_ids = array();
		$dialog_exist = array();
		
		$q = $db->query("SELECT id,in_read,chat_id,is_new,is_upd FROM vk_dialogs");
		while($row = $db->return_row($q)){
			$dialog_exist[$row['id']][$row['chat_id']] = array('read' => $row['in_read'], 'chat' => $row['chat_id'], 'new' => $row['is_new'], 'upd' => $row['is_upd']);
		}
		
		// Get returned IDs
		foreach($api_dialogs as $pk => $pv){
			//print_r($pv);
			if($pv['conversation']['peer']['type'] == 'user'){ // Filter Users from Groups and Chats
				$dialog_ids .= ($dialog_ids != '' ? ',' : '').$pv['conversation']['peer']['id'];
			}
			if($pv['conversation']['peer']['type'] == 'group'){
				$dialog_group_ids .= ($dialog_group_ids != '' ? ',' : '').abs($pv['conversation']['peer']['id']);
			}
			// Insert OR update dialog
			$multi = array('on' => 0, 'chat_id' => 0, 'users' => 0);
			if($pv['conversation']['peer']['type'] == 'chat'){
				$multi = array(
					'on' => 1,
					'chat_id' => $pv['conversation']['peer']['local_id'],
					'users' => $pv['conversation']['chat_settings']['members_count']);
			}
			$f->dialog_insert($pv,$multi,$dialog_exist);
		}
		
		if($dialog_ids != ''){
			$q = $db->query("SELECT * FROM vk_profiles WHERE id IN(".$dialog_ids.")");
			$dialog_ids = explode(',',$dialog_ids);
			
			while($row = $db->return_row($q)){
				if(in_array($row['id'],$dialog_ids)){
					// Existing profile. Check it for changes.
					
					// Remove profile id from known list
					$k = array_search($row['id'],$dialog_ids);
					unset($dialog_ids[$k]);
				}
			}
			
			// Set last profiles as new and save em
			if(!empty($dialog_ids)){
				// Get data to new profiles array
				foreach($api_dialogs as $ak => $av){
					if(in_array($av['conversation']['peer']['id'],$dialog_ids)){
						$dialog_new_ids[$av['conversation']['peer']['id']] = $av['conversation']['peer']['id'];
					}
				}
				
				$profile_data = '';
				if(!empty($dialog_new_ids)){
					// Get Users info
					// Warning: API limit is 1000 users via query, there is no limit check
					// because most dialogs would be 1 on 1 and within limit of 100 dialogs
					// per query chance to get overhead is low. Maybe fix this later...
					$profile_api = $vk->api('users.get', array(
						'user_ids' => implode(',',$dialog_new_ids),
						'fields' => 'screen_name,first_name,last_name,sex,photo_100'
					));
					
					foreach($profile_api['response'] as $pk => $pv){
						if(in_array($pv['id'],$dialog_new_ids)){
							$dialog_new_ids[$pv['id']] = $pv;
						}
					}
					
					// Make import query string
					foreach($dialog_new_ids as $k => $v){
						if(!isset($v['screen_name'])){ $v['screen_name'] = 'id'.$v['id']; }
						$profile_data .= ($profile_data != '' ? ',' : '')."({$v['id']},'".$db->real_escape($v['first_name'])."','".$db->real_escape($v['last_name'])."',{$v['sex']},'{$v['screen_name']}','{$v['photo_100']}','')";
					}
					
					// If we have data to import, do it!
					if($profile_data != ''){
						$q = $db->query("INSERT INTO vk_profiles (`id`,`first_name`,`last_name`,`sex`,`nick`,`photo_uri`,`photo_path`) VALUES ".$profile_data);
					}
				}
			} // end new profiles
		}
		
		if($dialog_group_ids != '' && true == false){
			$q = $db->query("SELECT * FROM vk_groups WHERE id IN(".$dialog_group_ids.")");
			$dialog_group_ids = explode(',',$dialog_group_ids);
			
			while($row = $db->return_row($q)){
				if(in_array($row['id'],$dialog_group_ids)){
					// Existing profile. Check it for changes.
					
					// Remove profile id from known list
					$k = array_search($row['id'],$dialog_group_ids);
					unset($dialog_group_ids[$k]);
				}
			}
			
			// Set last profiles as new and save em
			if(!empty($dialog_group_ids)){
				// Get data to new profiles array
				foreach($api_dialogs as $ak => $av){
					$gid = abs($av['conversation']['peer']['id']);
					if(in_array($gid,$dialog_group_ids)){
						$dialog_new_group_ids[$gid] = $gid;
					}
				}
				
				$group_data = '';
				if(!empty($dialog_new_group_ids)){
					// Get Groups info
					$group_api = $vk->api('groups.getById', array(
						'group_ids' => implode(',',$dialog_new_group_ids),
						'fields' => 'name,screen_name,photo_100'
					));
					
					foreach($group_api['response'] as $pk => $pv){
						if(in_array($pv['id'],$dialog_new_group_ids)){
							$dialog_new_group_ids[$pv['id']] = $pv;
						}
					}
					
					// Make import query string
					foreach($dialog_new_group_ids as $k => $v){
						$group_data .= ($group_data != '' ? ',' : '')."({$v['id']},'".$db->real_escape($v['name'])."','{$v['screen_name']}','{$v['photo_100']}','')";
					}
					
					// If we have data to import, do it!
					if($group_data != ''){
						$q = $db->query("INSERT INTO vk_groups (`id`,`name`,`nick`,`photo_uri`,`photo_path`) VALUES ".$group_data);
					}
				}
			} // end new groups
		}
	} // Check & Process END
		
	// I want this logic in one line, but this blow my mind so...
	$to = 0;
	if($offset == 0){
		$to = $count;
		if($count > $vk_dialogs_total){
			$to = $vk_dialogs_total;
		}
	} else {
		if(($count+$offset) > $vk_dialogs_total){
			$to = $vk_dialogs_total;
		} else {
			$to = $count+$offset;
		}
	}
	if($offset > 0){ $ot = $offset; } else { $ot = 1; }
	
	$output['response']['msg'][] = '<div>Получаем диалоги <b> '.$ot.' - '.$to.' / '.$vk_dialogs_total.'</b> из ВК.</div>';
	
	// Let's recount dialogs
	$q5 = $db->query("UPDATE vk_counters SET `dialogs` = (SELECT COUNT(*) FROM vk_dialogs)");
	
		// If we done with all dialogs
		if(($offset+$count) >= $vk_dialogs_total){
			// No unsynced dialogs left. This is the end...
			$output['response']['msg'][] = '<div class="alert alert-success mb-0" role="alert"><strong>Товарищ майор!</strong> Синхронизация диалогов завершена. Чтобы начать проверку сообщений снимите с паузы.</div>';
		} else {
			// Some dialogs is not synced yed
			$output['response']['msg'][] = '<div>Перехожу к следующей порции диалогов...</div>';
		
			// Calculate offset and reload page
			$offset_new = $offset+$count;
			$output['response']['next_uri'] = '/ajax/sync-message.php?do=dlg&offset='.$offset_new;
			$output['response']['total'] = $vk_dialogs_total;
		}

	} // Do dialog END
	
		// Do = Next
		if($do == 'next'){
			$don = true;
			// Check do we need sync updated or new dialogs?
			$q0 = $db->query_row("SELECT id,date FROM vk_dialogs WHERE `is_new` = 1 OR `is_upd` = 1 ORDER BY `id` DESC LIMIT 1");
			if(!empty($q0['date'])){
				$output['response']['msg'][] = '<div>Найдены сообщения. Нажмите продолжить чтобы начать получение новых сообщений.</div>';
				$output['response']['next_uri'] = '/ajax/sync-message.php?do=msg&offset=0&dlg_id='.$q0['id'].'&dlg_date='.$q0['date'];
			} else {
				$output['response']['msg'][] = '<div>Сообщений требующих синхронизации не найдено.</div>';
			}
		} // Do next END
		
		
		// Do = Messages
		if($do == 'msg'){
			// Check Dialog ID
			$q = $db->query_row("SELECT * FROM vk_dialogs WHERE `id` = {$dlg_id} AND `date` = {$dlg_date}");
			if(!empty($q['date']) && !empty($q['in_read'])){
				
				$quick = ($q['is_new'] == 1) ? false : true;
				$count = 200; // Maximum: 200
				$output['response']['done'] = $count;
				
				$peer = $q['id'];
				if($q['chat_id'] != 0){ $peer = 2000000000 + $q['chat_id']; }	// Group Chat ID
				
				// We logged in, get VK dialog list
				$api = $vk->api('messages.getHistory', array(
					'offset' => $offset,
					'count' => $count,
					'peer_id' => $peer
				));
	
				$api_msg = array();
				$vk_msg_total = 0;
	
				if($api['response'] != ''){
		
					$don = true;
					$api_msg = $api['response']['items'];
					$vk_msg_total = $api['response']['count'];
					
					if($offset == 0){
						// Send 'count' as total
						if($quick == false){
							$output['response']['total'] = $api['response']['count'];
						}
						// Send difference between saved & new messages
						if($quick == true){
							$output['response']['total'] = $api['response']['in_read'] - $q['in_read'];
						}
					}
		
				}
	
				
				if(!empty($api_msg)){
		
					foreach($api_msg as $k => $v){
						$attach = 0;
						$forward = 0;
						$forward_attach = 0;
			
			// Check attachments
			
			if(!empty($v['attachments'])){
				$attach = 1;
				
				foreach($v['attachments'] as $atv => $atk){
					// Attach - Photo
					if($atk['type'] == 'photo'){
						// Check do we have this attach already?
						$at = $db->query_row("SELECT id FROM vk_photos WHERE id = ".$atk['photo']['id']);
						// Attach found, make a link
						if(!empty($at['id']) && $atk['photo']['owner_id'] == $vk_session['vk_user']){
							// Insert OR update
							$f->msg_attach_update($v['id'],$atk,SYNC_MSG_DEBUG);
						} else {
							$photo_uri = $f->get_largest_photo($atk['photo']);
							
							// Save information about attach
							$f->msg_attach_insert($v['id'],$atk,$photo_uri,SYNC_MSG_DEBUG);
						}
					}
					
					// Attach - Video
					if($atk['type'] == 'video'){
						// Check do we have this attach already?
						$at = $db->query_row("SELECT id FROM vk_videos WHERE id = ".$atk['video']['id']);
						// Attach found, make a link
						if(!empty($at['id']) && $atk['video']['owner_id'] == $vk_session['vk_user']){
							// Insert OR update
							$f->msg_attach_update($v['id'],$atk,SYNC_MSG_DEBUG);
						} else {
							$photo_uri = $f->get_largest_photo($atk['video']);
							$atk['video']['player'] = '';
							
							// Get video player code for external attach
							$v_api = $vk->api('video.get', array(
								'videos' => $atk['video']['owner_id'].'_'.$atk['video']['id'].($atk['video']['access_key'] != '' ? '_'.$atk['video']['access_key'] : ''),
								'extended' => 0, // возвращать ли информацию о настройках приватности видео для текущего пользователя
								'offset' => 0,
								'count' => 1
							));
							
							if(isset($v_api['response']['items'][0]['player']) && $v_api['response']['items'][0]['player'] != ''){
								$atk['video']['player'] = $v_api['response']['items'][0]['player'];
							}
							
							// Save information about attach
							$f->msg_attach_insert($v['id'],$atk,$photo_uri,SYNC_MSG_DEBUG);
						}
					}
					
					// Attach - Link
					if($atk['type'] == 'link'){
						// For links we use a date as id because link type does not have a id
						$atk['link']['id'] = $v['date'];
						$atk['link']['owner_id'] = 0;//$v['owner_id'];
						$atk['link']['date'] = $v['date'];
						$atk['link']['access_key'] = '';
						// Check do we have this attach already?
						$at = $db->query_row("SELECT attach_id FROM vk_messages_attach WHERE attach_id = ".$atk['link']['id']);
						// Attach found, just skip it ><
						if(!empty($at['attach_id'])){
							// Insert OR update
							$f->msg_attach_update($v['id'],$atk,SYNC_MSG_DEBUG);
						} else {
							if(isset($atk['link']['photo'])){
								$photo_uri = $f->get_largest_photo($atk['link']['photo']);
								$atk['link']['width']  = (isset($atk['link']['photo']['width'])) ? $atk['link']['photo']['width'] : 0 ;
								$atk['link']['height'] = (isset($atk['link']['photo']['height'])) ? $atk['link']['photo']['height'] : 0;
							} else {
								$photo_uri = '';
							}
							
							// Save information about attach
							$f->msg_attach_insert($v['id'],$atk,$photo_uri,SYNC_MSG_DEBUG);
						}
					}
					
					
					// Attach - Document
					/*
						title -> title
						size -> duration
						ext -> text
						url -> uri ( for local copy path )
						date -> date
						type -> not saved
						preview (
							photo -> link_url ( for local copy player )
							width -> width
							height -> height
						)
					*/
					if($atk['type'] == 'doc'){
						// Check do we have this attach already?
						$at = $db->query_row("SELECT id FROM vk_docs WHERE id = ".$atk['doc']['id']);
						$photo_uri = '';
						// Attach found, make a link
						if(!empty($at['id']) && $atk['doc']['owner_id'] == $vk_session['vk_user']){
							// Insert OR update
							$f->msg_attach_update($v['id'],$atk,SYNC_MSG_DEBUG);
						} else {
							$atk['doc']['caption'] = $atk['doc']['ext'];
							$atk['doc']['width'] = 0;
							$atk['doc']['height'] = 0;
							$atk['doc']['duration'] = $atk['doc']['size'];
							$atk['doc']['text'] = $atk['doc']['ext'];
							
							if(isset($atk['doc']['preview'])){
								// Images
								if(isset($atk['doc']['preview']['photo'])){
									// Get biggest preview
									$sizes = $f->get_largest_doc_image($atk['doc']['preview']['photo']['sizes']);
									if($sizes['pre'] != ''){
										$photo_uri = $sizes['pre'];
										$atk['doc']['width'] = $sizes['prew'];
										$atk['doc']['height'] = $sizes['preh'];
									}
								}
							} // Preview end

							// Save information about attach
							$f->msg_attach_insert($v['id'],$atk,$photo_uri,SYNC_MSG_DEBUG);
						}
					}
					
					// Attach - Sticker
					if($atk['type'] == 'sticker'){
						
						if(!isset($atk['sticker']['sticker_id'])){ $atk['sticker']['sticker_id'] = $atk['sticker']['id']; }
						// Check do we have this attach already?
						$at = $db->query_row("SELECT sticker FROM vk_stickers WHERE product = ".$atk['sticker']['product_id']." AND sticker = ".$atk['sticker']['sticker_id']);
						// Attach found, make a link
						if(!empty($at['sticker'])){
							// Insert OR update
							$f->msg_attach_update($v['id'],$atk,SYNC_MSG_DEBUG);
						} else {
							$atk['sticker']['caption'] = '';
							$atk['sticker']['width'] = 0;
							$atk['sticker']['height'] = 0;
							$atk['sticker']['duration'] = 0;
							$atk['sticker']['text'] = '';
							$atk['sticker']['owner_id'] = 0;
							$atk['sticker']['date'] = $v['date'];
							$atk['sticker']['id'] = 0;
							
							$sticker = $f->get_sticker_image($atk['sticker'],true);
							
							if($sticker['pre'] != ''){
								$photo_uri = $sticker['pre'];
								$atk['sticker']['width'] = $sticker['prew'];
								$atk['sticker']['height'] = $sticker['preh'];
							}
							// Save information about attach
							$f->msg_attach_insert($v['id'],$atk,$photo_uri,SYNC_MSG_DEBUG);
						}
					} // STICKER end
					
					// Attach - Wall
					if($atk['type'] == 'wall'){
						$atk['wall']['caption'] = '';
						$atk['wall']['owner_id'] = $atk['wall']['from_id'];
						$atk['wall']['width'] = 0;
						$atk['wall']['height'] = 0;
						$atk['wall']['duration'] = 0;
						$atk['wall']['title'] = '';
						$photo_uri = '';
						$f->msg_attach_insert($v['id'],$atk,$photo_uri,SYNC_MSG_DEBUG);
					} // WALL end
					
				} // Foreach end
			} // Attachments end

						// Insert OR update message
						$f->dialog_message_insert($v,$attach,$forward,SYNC_MSG_DEBUG);
			
						// Fast sync option
						// Check the date of the last post to our posts. If found, stop sync.
						if($quick == true && $q['in_read'] > 0 && $v['id'] <= $q['in_read']){
							$quick_sync_stop = true;
						}
					}
		

				} // Check & Process END
				
				// I want this logic in one line, but this blow my mind so...
				$to = 0;
				if($offset == 0){
					$to = $count;
					if($count > $vk_msg_total){
						$to = $vk_msg_total;
					}
				} else {
					if(($count+$offset) > $vk_msg_total){
						$to = $vk_msg_total;
					} else {
						$to = $count+$offset;
					}
				}
				
				if($offset > 0){ $ot = $offset; } else { $ot = 1; }
				
				$output['response']['msg'][] = '<div>Получаем сообщения <b> '.$ot.' - '.$to.' / '.$vk_msg_total.'</b>'.($quick == true ? ' (быстрая синхронизация) ' : '').'</div>';
				
				if($quick == true && $quick_sync_stop == true){
					if(SYNC_MSG_DEBUG == false){
					// Update current dialog status to done
					$q1 = $db->query("UPDATE vk_dialogs SET `is_new` = 0, `is_upd` = 0 WHERE `id` = ".$dlg_id." AND `date` = ".$dlg_date);
					}
					
					// Check do we need sync updated or new dialogs?
					$q2 = $db->query_row("SELECT id,date FROM vk_dialogs WHERE `is_new` = 1 OR `is_upd` = 1 ORDER BY `id` DESC LIMIT 1");
					if(!empty($q2['date'])){
						$output['response']['msg'][] = '<div>Найден следующий диалог требующий синхронизации.</div>';
						$output['response']['next_uri'] = '/ajax/sync-message.php?do=msg&offset=0&dlg_id='.$q2['id'].'&dlg_date='.$q2['date'];
					} else {
						// No unsynced messages left. This is the end...
						$output['response']['msg'][] = '<div class="alert alert-success mb-0" role="alert"><strong>Все ходы записаны!</strong> Быстрая синхронизация сообщений завершена.</div>';
					}
				} else {
	
					// If we done with all messages
					if(($offset+$count) >= $vk_msg_total){
						if(SYNC_MSG_DEBUG == false){
						// Update current dialog status to done
						$q1 = $db->query("UPDATE vk_dialogs SET `is_new` = 0, `is_upd` = 0 WHERE `id` = ".$dlg_id." AND `date` = ".$dlg_date);
						}
					
						// Check do we need sync updated or new dialogs?
						$q2 = $db->query_row("SELECT id,date FROM vk_dialogs WHERE `is_new` = 1 OR `is_upd` = 1 ORDER BY `id` DESC LIMIT 1");
						if(!empty($q2['date'])){
							$output['response']['msg'][] = '<div>Найден следующий диалог требующий синхронизации.</div>';
							$output['response']['next_uri'] = '/ajax/sync-message.php?do=msg&offset=0&dlg_id='.$q2['id'].'&dlg_date='.$q2['date'];
						} else {
							// No unsynced messages left. This is the end...
							$output['response']['msg'][] = '<div class="alert alert-success mb-0" role="alert"><strong>Все ходы записаны!</strong> Быстрая синхронизация сообщений завершена.</div>';
						}
					} else {
						// Some messages on dialog is not synced yed
						$output['response']['msg'][] = '<div>Перехожу к следующей порции сообщений...</div>';
		
						// Calculate offset and reload page
						$offset_new = $offset+$count;
						$output['response']['next_uri'] = "/ajax/sync-message.php?do=msg&offset=".$offset_new."&dlg_id=".$dlg_id."&dlg_date=".$dlg_date;
					}
	
				} // Fast sync end
				
				
			} else {
				$output['error'] = true;
$output['response']['error_msg'] = <<<E
    <div class="alert alert-info mb-0" role="alert">Диалог {$dlg_id} не найден.</div>
E;
			}
		} // Do msg END
	
	
	// END Of catch
	} catch (Exception $error) {
		$output['error'] = true;
		$output['response']['error_msg'] = '<div>'.$error->getMessage().'</div>';
	}
// end of Token Check
} else {
	// Token is NOT valid, re-auth?
$output['error'] = true;
$output['response']['error_msg'] = <<<E
    <div class="alert alert-danger mb-0" role="alert"><span>Внимание!</span> Токен является недействительным. Необходимо авторизироваться.</div>
E;
}

if($don == false && $token_valid == true){
$output['error'] = true;
$output['response']['error_msg'] = <<<E
    <div class="alert alert-info mb-0" role="alert">Нет заданий для синхронизации</div>
E;
}

$db->close($res);

print json_encode($output);

?>