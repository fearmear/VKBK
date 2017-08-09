<?php

class func {
	
	function func(){
		return true;
	}
	
	function windows_path_alias($url,$type){
	    if($type == 'video'){ return preg_replace("/^Y\:\/VKBK\/video\//","/vkbk-video/",$url); }
	    if($type == 'photo'){ return preg_replace("/^K\:\/VKBK\/photo\//","/vkbk-photo/",$url); }
	    if($type == 'audio'){ return preg_replace("/^W\:\/VKBK\/music\//","/vkbk-music/",$url); }
	    if($type == 'docs'){  return preg_replace("/^K\:\/VKBK\/docs\//" ,"/vkbk-docs/" ,$url); }
	}
	
	/*
	    Function: wall_attach_update
	    Inserts information about post attach to DB if attach is found in local
	    In:
	    id - attachID,
	    atk - attachData
	*/
	function wall_attach_update($id,$atk){
	    global $db;
	    
	    $type = $atk['type'];
	    
	    // Insert OR update
	    $q = $db->query("INSERT INTO `vk_attach`
	    (`uid`,`wall_id`,`type`,`is_local`,`attach_id`,`owner_id`,`uri`,`path`,`width`,`height`,`text`,`date`,`access_key`,`title`,`duration`,`player`,`link_url`,`caption`)
	    VALUES
	    (NULL,{$id},'{$type}',1,{$atk[$type]['id']},0,'','',0,0,'',0,'','',0,'','','')
	    ON DUPLICATE KEY UPDATE
	    `wall_id` = {$id}, `type` = '{$type}', `is_local` = 1, `attach_id` = {$atk[$type]['id']}
	    ");
	}
	
	/*
	    Function: wall_attach_insert
	    Inserts information about post attach to DB if attach is NOT found in local
	    In:
	    id - attachID,
	    atk - attachData,
	    photo_uri - uri of image (photo & video) if it not stored in user albums
	*/
	function wall_attach_insert($id,$atk,$photo_uri){
	    global $db;
	    //print_r($atk);
	    $type = $atk['type'];
	    $text = '';
	    if($type == 'photo'){ $text = $atk['photo']['text']; }
	    if($type == 'video'){ $text = $atk['video']['description']; }
	    if($type == 'link'){  $text = $atk['link']['description']; }
	    
	    // Prepare empty data if another type of attach
	    $atk[$type]['width']       = !isset($atk[$type]['width'])      ? 0  : $atk[$type]['width'];
	    $atk[$type]['height']      = !isset($atk[$type]['height'])     ? 0  : $atk[$type]['height'];
	    $atk[$type]['duration']    = !isset($atk[$type]['duration'])   ? 0  : $atk[$type]['duration'];
	    $atk[$type]['title']       = !isset($atk[$type]['title'])      ? '' : $atk[$type]['title'];
	    $atk[$type]['player']      = !isset($atk[$type]['player'])     ? '' : $atk[$type]['player'];
	    $atk[$type]['url']         = !isset($atk[$type]['url'])        ? '' : $atk[$type]['url'];;
	    $atk[$type]['caption']     = !isset($atk[$type]['caption'])    ? '' : $atk[$type]['caption'];
	    $atk[$type]['access_key']  = !isset($atk[$type]['access_key']) ? '' : $atk[$type]['access_key'];
	    
	    // Save information about attach
	    $q = $db->query("INSERT INTO `vk_attach`
	    (`uid`,`wall_id`,`type`,`is_local`,`attach_id`,`owner_id`,`uri`,`path`,`width`,`height`,`text`,`date`,`access_key`,`title`,`duration`,`player`,`link_url`,`caption`)
	    VALUES
	    (NULL,{$id},'{$type}',0,{$atk[$type]['id']},{$atk[$type]['owner_id']},'{$photo_uri}','',{$atk[$type]['width']},{$atk[$type]['height']},'".$db->real_escape($text)."',{$atk[$type]['date']},'{$atk[$type]['access_key']}','".$db->real_escape($atk[$type]['title'])."',{$atk[$type]['duration']},'{$atk[$type]['player']}','{$atk[$type]['url']}','".$db->real_escape($atk[$type]['caption'])."')
	    ON DUPLICATE KEY UPDATE
	    `wall_id` = {$id}, `type` = '{$type}', `is_local` = 0, `attach_id` = {$atk[$type]['id']}, `owner_id` = {$atk[$type]['owner_id']}, `uri` = '{$photo_uri}', `width` = {$atk[$type]['width']}, `height` = {$atk[$type]['height']}, `text` = '".$db->real_escape($text)."', `date` = {$atk[$type]['date']}, `access_key` = '{$atk[$type]['access_key']}', `title` = '".$db->real_escape($atk[$type]['title'])."', `duration` = {$atk[$type]['duration']}, `player` = '{$atk[$type]['player']}', `link_url` = '{$atk[$type]['url']}', `caption` = '".$db->real_escape($atk[$type]['caption'])."'
	    ");
	}
	
	/*
	    Function: wall_post_insert
	    Saves wall post and repost body to DB
	    In:
	    v - postData,
	    attach - post contains attachment
	    repost - post contains another post
	    is_repost - post are inside
	*/
	function wall_post_insert($v,$attach,$repost,$is_repost){
	    global $db;
	    
	    $q = $db->query("INSERT INTO `vk_wall`
	    (`id`,`from_id`,`owner_id`,`date`,`post_type`,`text`,`attach`,`repost`,`is_repost`)
	    VALUES
	    ({$v['id']},{$v['from_id']},{$v['owner_id']},{$v['date']},'{$v['post_type']}','".$db->real_escape($this->removeEmoji($v['text']))."',{$attach},{$repost},{$is_repost})
	    ON DUPLICATE KEY UPDATE
	    `from_id` = {$v['from_id']}, `owner_id` = {$v['owner_id']}, `date` = {$v['date']}, `post_type` = '{$v['post_type']}', `text` = '".$db->real_escape($this->removeEmoji($v['text']))."', `attach` = {$attach}, `repost` = {$repost}, `is_repost` = {$is_repost}
	    ");
	}
	
	/*
	    Function: get_largest_photo
	    Returns a largest photo url
	    In: data array
	    Out: url
	*/
	function get_largest_photo($data){
	    $photo_uri = '';
	        if(isset($data['photo_2560'])){ $photo_uri = $data['photo_2560']; }
	    elseif(isset($data['photo_1280'])){ $photo_uri = $data['photo_1280'];}
	    elseif(isset($data['photo_807'])){  $photo_uri = $data['photo_807'];}
	    elseif(isset($data['photo_800'])){  $photo_uri = $data['photo_800'];} // Video
	    elseif(isset($data['photo_640'])){  $photo_uri = $data['photo_640'];} // Video
	    elseif(isset($data['photo_604'])){  $photo_uri = $data['photo_604'];}
	    elseif(isset($data['photo_320'])){  $photo_uri = $data['photo_320'];} // Video
	    elseif(isset($data['photo_130'])){  $photo_uri = $data['photo_130'];}
	    elseif(isset($data['photo_75'])){   $photo_uri = $data['photo_75'];}
	    return $photo_uri;
	}
	
	/*
	  function get_largest_doc_image
	  Returns a largest image of document preview
	  In: data array
	  Out: array(uri, width, height)
	*/
	function get_largest_doc_image($data){
		$image = array('pre'=>"",'prew'=>0,'preh'=>0);
		foreach($data as $pk => $pv){
			if(    $pv['type'] == 's'){ // 75px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'm'){ // 130 px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'x'){ // 604 px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'o'){ // 3:2 130 px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'p'){ // 3:2 200 px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'q'){ // 3:2 320 px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'r'){ // 3:2 510 px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'y'){ // 807 px
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'z'){ // 1082x1024
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
			elseif($pv['type'] == 'w'){ // 2560x2048
				$image['pre'] = $pv['src']; $image['prew'] = $pv['width']; $image['preh'] = $pv['height']; }
		}
		return $image;
	}
	
	function wall_date_format($time){
	    $date = '';
	    $y = date("YYYY");
	    if($y != date("YYYY",$time)){    $date = date("d M Y H:i",$time); }
	    if($y == date("YYYY",$time)){    $date = date("d M в H:i",$time);
		$w = date("W");
		$d = date("z");
		if($w-date("W",$time) == 1){ $date = date("на этой неделе в H:i",$time); }
		if($d-date("z",$time) == 2){ $date = date("позавчера в H:i",$time); }
		if($d-date("z",$time) == 1){ $date = date("вчера в H:i",$time); }
		if($d == date("z",$time)){   $date = date("сегодня в H:i",$time);
		    $h = date("H");
		    if($h-date("H",$time) == 1){
			                     $date = "час назад";
		    }
		}
	    }
	    return $date;
	}
	
	function wall_show_post($row,$repost,$repost_body){
	    global $cfg, $db, $skin;
	    
	    $output = '';

	    // Load profiles for posts
	    if($row['from_id'] > 0){
		    $pr = $db->query_row("SELECT * FROM vk_profiles WHERE `id` = ".$row['from_id']);
        	    $path = 'profiles';
		    $who = $pr['first_name'].' '.$pr['last_name'];
	    } else {
		    $pr = $db->query_row("SELECT * FROM vk_groups WHERE `id` = ".abs($row['from_id']));
		    $path = 'groups';
		    $who = $pr['name'];
	    }

	    // Attachments
	    $attach = array(
		'local_photo'  => '',
		'attach_photo' => '',
		'local_video'  => '',
		'attach_video' => '',
		'attach_link'  => '',
		'local_audio'  => '',
		'attach_audio' => '',
		'local_doc'    => '',
		'attach_doc'   => ''
	    );
	
	    if($row['attach'] == 1){
		$q = $db->query("SELECT * FROM vk_attach WHERE wall_id = ".$row['id']);
		while($at_row = $db->return_row($q)){
			if($at_row['type'] == 'photo' && $at_row['is_local'] == 1){
				$attach['local_photo'] .= ($attach['local_photo'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'photo' && $at_row['is_local'] == 0 && $at_row['path'] != ''){
				$attach['attach_photo'] .= ($attach['attach_photo'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'video' && $at_row['is_local'] == 1){
				$attach['local_video'] .= ($attach['local_video'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'video' && $at_row['is_local'] == 0 && $at_row['path'] != ''){
				$attach['attach_video'] .= ($attach['attach_video'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'link'){
				$attach['attach_link'] .= ($attach['attach_link'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'audio' && $at_row['is_local'] == 1){
				$attach['local_audio'] .= ($attach['local_audio'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'audio' && $at_row['is_local'] == 0 && $at_row['path'] != ''){
				$attach['attach_audio'] .= ($attach['attach_audio'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'doc' && $at_row['is_local'] == 1){
				$attach['local_doc'] .= ($attach['local_doc'] != '' ? ',' : '').$at_row['attach_id'];
			}
			if($at_row['type'] == 'doc' && $at_row['is_local'] == 0 && $at_row['player'] != ''){
				$attach['attach_doc'] .= ($attach['attach_doc'] != '' ? ',' : '').$at_row['attach_id'];
			}
		}
	    }

	    $full_date = date("d M Y H:i",$row['date']);
	    $row['date'] = $this->wall_date_format($row['date']);
	    if($row['text'] != ''){ $row['text'] = '<div style="margin-bottom:10px;">'.nl2br($this->wall_post_parse($row['text'])).'</div>'; }
	    $tmp_box = '';
	    $tmp_class = 'col-sm-6 col-sm-offset-3 wall-box';
$tmp_postid = <<<E
    <a class="post-id wallious fancybox" data-fancybox data-type="iframe" data-title-id="#{$row['id']}" href="ajax/wall-post.php?p={$row['id']}" onClick="javascript:urlCommands.urlPush({post:{$row['id']}});">#{$row['id']}</a>
E;
	    if($repost === true){
		$tmp_box = 'repost';
		$tmp_class = 'col-sm-12 repost-box';
	    }
	    if($repost === 'single'){
		$tmp_class = 'col-sm-12 wall-box';
		$tmp_postid = '';
	    }

$output .= <<<E
<div class="row {$tmp_box}">
    <div class="{$tmp_class}">
	{$tmp_postid}
	<img src="data/{$path}/{$pr['photo_path']}" class="wall-ava" />
	<div class="wall-head">
		<a href="javascript:;">{$who}</a><br/><span class="full-date" data-placement="right" data-toggle="tooltip" data-original-title="{$full_date}">{$row['date']}</span>
	</div>
	<div style="clear:both;"></div>
	{$row['text']}{$repost_body}
E;


foreach($attach as $qk => $qv){

$attach_query = false;
$qclass = '';
if($qk == 'local_photo' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_photos WHERE id IN(".$qv.")");
	$attach_query = true;
	$qclass = 'free-wall';
}
if($qk == 'attach_photo' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_attach WHERE attach_id IN(".$qv.") AND wall_id = ".$row['id']);
	$attach_query = true;
	$qclass = 'free-wall';
}

if($qk == 'local_video' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_videos WHERE id IN(".$qv.")");
	$attach_query = true;
}
if($qk == 'attach_video' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_attach WHERE attach_id IN(".$qv.") AND wall_id = ".$row['id']);
	$attach_query = true;
}

if($qk == 'attach_link' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_attach WHERE attach_id IN(".$qv.") AND wall_id = ".$row['id']);
	$attach_query = true;
	$qclass = 'free-wall';
}

if($qk == 'local_audio' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_music WHERE id IN(".$qv.")");
	$attach_query = true;
}
if($qk == 'attach_audio' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_attach WHERE attach_id IN(".$qv.") AND wall_id = ".$row['id']);
	$attach_query = true;
}
if($qk == 'local_doc' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_docs WHERE id IN(".$qv.")");
	$attach_query = true;
}
if($qk == 'attach_doc' && $qv != ''){
	$q = $db->query("SELECT * FROM vk_attach WHERE attach_id IN(".$qv.") AND wall_id = ".$row['id']);
	$attach_query = true;
}
    
if($attach_query == true){
	$output .= '<div class="'.$qclass.'">';
	while($lph_row = $db->return_row($q)){
		// Let's try to guess what type of data we have received
		
		// Type - Photo or attach photo
		if((isset($lph_row['type']) && $lph_row['type'] == 'photo') || isset($lph_row['album_id'])){
			// Rewrite for Alias
			if($cfg['vhost_alias'] == true && substr($lph_row['path'],0,4) != 'http'){
				$lph_row['path'] = $this->windows_path_alias($lph_row['path'],'photo');
			}
$output .= <<<E
    <div class="brick" style='width:{$cfg['wall_layout_width']}px;'><a class="fancybox" data-fancybox="images" rel="p{$row['id']}" href="{$lph_row['path']}"><img style="width:100%" src="{$lph_row['path']}"></a></div>
E;
		} // end of attach photo
		
		// Remote Video Attach
		if(isset($lph_row['type']) && $lph_row['type'] == 'video'){
			// Rewrite for Alias
			if($cfg['vhost_alias'] == true && substr($lph_row['path'],0,4) != 'http'){
				$lph_row['path'] = $this->windows_path_alias($lph_row['path'],'video');
			}
			
			// Clean ref
			$lph_row['player'] = preg_replace("/\?__ref\=vk\.api/","",$lph_row['player']);
			
			// Youtube disable fkn Anontation Z
			if(strstr($lph_row['player'],'youtube.com') || strstr($lph_row['player'],'youtu.be')){
				$lph_row['player'] = $lph_row['player'].'?iv_load_policy=3';
			}

			if($lph_row['text'] != ''){ $lph_row['text'] = '<div style="margin-bottom:10px;">'.nl2br($lph_row['text']).'</div>'; }
			$lph_row['duration'] = $skin->seconds2human($lph_row['duration']);
$output .= <<<E
	<div class="wall-video-box">
	    <span class="label label-default wall-video-duration">{$lph_row['duration']}</span>
	    <a class="various fancybox" href="javascript:;" onclick="javascript:fbox_video_global('{$lph_row['player']}',1);" data-title-id="title-{$lph_row['attach_id']}" style="background-image:url('{$lph_row['path']}');"></a>
	</div>
	<h4 class="wall-video-header">{$lph_row['title']}</h4>
	<div id="title-{$lph_row['attach_id']}" style="display:none;">
	    {$lph_row['text']}
	    <div class="expander" onClick="expand_desc();">показать</div>
	</div>
E;
		} // end of attach video
		
		// Remote Link Attach
		if(isset($lph_row['type']) && $lph_row['type'] == 'link'){
			// Rewrite for Alias
			if($cfg['vhost_alias'] == true && substr($lph_row['path'],0,4) != 'http'){
				$lph_row['path'] = $this->windows_path_alias($lph_row['path'],'photo');
			}

			if($lph_row['text'] != ''){ $lph_row['text'] = '<div style="margin-bottom:10px;">'.nl2br($lph_row['text']).'</div>'; }
			if($lph_row['path'] != ''){
$output .= <<<E
    <div class="wall-link-img"><a class="fancybox" data-fancybox="images" rel="p{$row['id']}" href="{$lph_row['path']}"><img style="width:100%" src="{$lph_row['path']}"></a><a href="{$lph_row['link_url']}" class="wall-link-caption" rel="nofollow noreferrer" target="_blank"><i class="fa fa-chain"></i>&nbsp;{$lph_row['caption']}</a></div>
E;
$output .= <<<E
<div class="col-sm-12" style="border:1px solid rgba(0,20,51,.12);">
	<h4>{$lph_row['title']}</h4>
	<p class="wall-description">{$lph_row['text']}</p>
</div>
E;
			} else {
$output .= <<<E
<div class="col-sm-12">
	<h5><a href="{$lph_row['link_url']}" rel="nofollow noreferrer" target="_blank"><i class="fa fa-share"></i> {$lph_row['title']}</a></h5>
	<p class="wall-description">{$lph_row['text']}</p>
</div>
E;
			}

		} // end of attach link
		
		// Remote Audio Attach
		if(isset($lph_row['type']) && $lph_row['type'] == 'audio'){
			// Rewrite for Alias
			if($cfg['vhost_alias'] == true && substr($lph_row['path'],0,4) != 'http'){
				$lph_row['path'] = $this->windows_path_alias($lph_row['path'],'audio');
			}
			
			if($lph_row['path'] != ''){
$output .= <<<E
<div class="col-sm-12" style="margin:4px auto 0 auto;font-size:12px;">
    {$lph_row['caption']} - {$lph_row['title']}
    <audio controls preload="none" style="width:100%;">
	<source src="{$lph_row['path']}" type="audio/mpeg">
	Ваш браузер не поддерживает HTML5 аудио.
    </audio>
</div>
E;
			}

		} // end of attach audio
		
		// Type - Document or attach document
		if((isset($lph_row['type']) && $lph_row['type'] == 'doc')){
			// Rewrite for Alias
			if($cfg['vhost_alias'] == true && substr($lph_row['path'],0,4) != 'http'){
				$lph_row['path'] = $this->windows_path_alias($lph_row['path'],'docs');
			}
			// Attach
			if(isset($lph_row['player'])){
				// Have preview
				if($lph_row['path'] != ''){
					// Rewrite for Alias
					if($cfg['vhost_alias'] == true && substr($lph_row['player'],0,4) != 'http'){
						$lph_row['player'] = $this->windows_path_alias($lph_row['player'],'docs');
					}
					$animated = '';
					if(strtolower(substr($lph_row['player'],-3)) == "gif"){
						$animated = 'class="doc-gif" data-docsrc="'.$lph_row['player'].'" data-docpre="'.$lph_row['path'].'"';
					}
$output .= <<<E
    <div class="brick" style='width:100%;'><a class="fancybox" data-fancybox="images" rel="p{$row['id']}" href="{$lph_row['player']}"><img {$animated} style="width:100%" src="{$lph_row['path']}"></a></div>
E;
				} else {
					$lph_row['duration'] = $this->human_filesize($lph_row['duration']);
					$lph_row['caption'] = strtoupper($lph_row['caption']);
$output .= <<<E
<div class="col-sm-12">
	<h5><a href="{$lph_row['player']}" rel="nofollow noreferrer" target="_blank"><i class="fa fa-share"></i> {$lph_row['title']}</a></h5>
	<p class="wall-description"><span class="label label-default">{$lph_row['caption']}</span> {$lph_row['duration']}</p>
</div>
E;
				}
			}
			
		} // end of attach document
		
	}
	$output .= '</div>';
}

} // Foreach $attach end

$output .= <<<E
	</div>
</div>
E;
	    return $output;
	} // Wall Show Post end
	
	function wall_post_parse($text){
	    if($text != ''){
		$fnd = array(
		    '/\#([^\s\#]+)/',
		    '/\[([^\s\|]+)\|([^\]]+)\]/'
		);
		$rpl = array(
		    '<a href="https://new.vk.com/feed?section=search&q=%23\1" rel="norefferer" target="_blank"><i class="fa fa-tag"></i> \1</a>',
		    '<a href="https://new.vk.com/\1" rel="norefferer" target="_blank"><i class="fa fa-chain"></i> \2</a>'
		);
		$text = preg_replace($fnd,$rpl,$text);
		
		return $text;
	    } else {
		return false;
	    }
	}
	
	function human_filesize($bytes, $decimals = 2) {
		$size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}
	
	function human_thousand($num) {
		if(!is_numeric($num)){
			return false;
		} else {
			if($num >= 1000){
				return sprintf("%.1f", $num / 1000) . "k";
			}  else {
				return $num;
			}
		}
	}
	
	// Emoji clecn function by quantizer
	// https://gist.github.com/quantizer/5744907
	function removeEmoji($text) {
        $cleanText = "";

        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $cleanText = preg_replace($regexEmoticons, '', $text);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $cleanText = preg_replace($regexSymbols, '', $cleanText);

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $cleanText = preg_replace($regexTransport, '', $cleanText);

        return $cleanText;
	}

} // end of class

?>