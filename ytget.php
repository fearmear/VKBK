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

// Video Key
$key = isset($_GET['key']) ? $_GET['key'] : '';
// DB id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Service type
$s = isset($_GET['s']) ? preg_replace("/[^a-z]+/","",$_GET['s']) : '';

print <<<E
<div class="container">
          <h2 class="sub-header">Сохраниение видео (youtube-dl)</h2>
          <div class="table-responsive">
            <table class="table table-striped">
E;

if($key == ''){
print <<<E
<tr>
  <td>
    <div class="alert alert-danger" role="alert">Не указан ключ для видео</div>
  </td>
</tr>
E;
}

// Check video ID
$vid = $db->query_row("SELECT id FROM `vk_videos` WHERE id = {$id}");
if(!isset($vid['id']) || empty($vid['id'])){
print <<<E
<tr>
  <td>
    <div class="alert alert-danger" role="alert">Не удалось найти видео с данным ID</div>
  </td>
</tr>
E;
}

if($key != '' && isset($vid['id']) && $vid['id'] > 0 && $s != ''){

print <<<E
<tr>
 <td>
  Key: {$key}
 </td>
</tr>
<tr>	
 <td>
  <div style="width:100%;height:340px;overflow:hidden;position:relative;">
  <pre style="position:absolute;bottom:0;left:0;width:100%;">
E;

/* youtube-dl command line options
  --no-mark-watched		- Do not mark videos watched (YouTube only)
  -4					- Use IPv4
  --restrict-filenames	- Restrict filenames to only ASCII characters, and avoid "&" and spaces in filenames
  -w					- Do not overwrite files
  --no-part				- Do not use .part files - write directly into output file
  -o					- File would be saved as `YouTubeKey.ext` Example: DijY9NkGSak.mp4
  
  optional:
  -v					- debug
  
  For more options you can read official readme
  https://github.com/rg3/youtube-dl/blob/master/README.md#readme
*/

$youtubeDLlog = '';
$local = array(
	'path' => "",
	'size' => 0,
	'format' => "",
	'w' => 0,
	'h' => 0
);

// YouTube
if($s == 'yt'){
	$youtubeDLcmd = $cfg['yt_dl_path'].'youtube-dl.exe --no-mark-watched -4 --restrict-filenames -w --no-part --write-info-json -o "'.$cfg['video_path'].'data/'.$key.'.%(ext)s" https://youtu.be/'.$key;
}
// VK.com
if($s == 'vk'){
	$youtubeDLcmd = $cfg['yt_dl_path'].'youtube-dl.exe -4 --restrict-filenames -w --no-part --write-info-json -o "'.$cfg['video_path'].'data/vk-'.$vid['id'].'.%(ext)s" http://vk.com/video'.$key;
}

	ob_implicit_flush(true);
	ob_end_flush();
	passthru($youtubeDLcmd);

print <<<E
  </pre>
  </div>
 </td>
</tr>
E;

// Check info.json for... INFO! :D
$info = '';
if($s == 'yt'){	$info = $cfg['video_path'].'data/'.$key.'.info.json'; }
if($s == 'vk'){ $info = $cfg['video_path'].'data/vk-'.$vid['id'].'.info.json'; }

if(file_exists($info)){
	$handle = fopen($info, "r");
	$content = fread($handle, filesize($info));
	fclose($handle);
	$youtubeDLlog = json_decode($content);
	
	if(isset($youtubeDLlog->_filename)){
		$local['path'] = $youtubeDLlog->_filename;
		if($s == 'yt'){ $local['size'] = filesize($cfg['video_path'].'data/'.$key.'.'.$youtubeDLlog->ext); }
		if($s == 'vk'){ $local['size'] = filesize($cfg['video_path'].'data/vk-'.$vid['id'].'.'.$youtubeDLlog->ext); }
		$local['format'] = $youtubeDLlog->ext;
		$local['w'] = (isset($youtubeDLlog->width)) ? $youtubeDLlog->width : 0;
		$local['h'] = (isset($youtubeDLlog->height)) ? $youtubeDLlog->height : 0;
		
		$q = $db->query("UPDATE vk_videos SET `local_path` = '{$local['path']}', `local_size` = {$local['size']}, `local_format` = '{$local['format']}', `local_w` = {$local['w']}, `local_h` = {$local['h']} WHERE id = {$vid['id']}");
		if($q){
print <<<E
<tr>
  <td>
    <div class="alert alert-success" role="alert">Видеофайл сохранен.</div>
  </td>
</tr>
E;
		}
	}
	
} else {
	// No file?! We fail!
print <<<E
<tr>
  <td>
    <div class="alert alert-danger" role="alert">Шеф, всё пропало! ):</div>
  </td>
</tr>
E;
}

// End of IF KEY
}

print <<<E
            </table>
          </div>
</div>
E;

print $skin->footer(array('v'=>$version,'extend'=>''));

$db->close($res);

?>