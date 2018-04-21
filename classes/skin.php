<?php

class skin {
	
	function skin(){
		return true;
	}
	
	function header($s){
	    global $cfg;
	    $db_check = $this->check_db_ver() ? '' : '<div class="m-2 badge badge-danger db-expired"><i class="fas fa-exclamation-triangle"></i> Структура базы данных устарела. Обратитесь к <a href="update/index.html" target="_blank">инструкции</a> по обновлению перед синхронизацией. <i class="fas fa-exclamation-triangle"></i></div>';
		return <<<E
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>VKBK</title>
    <base href="/"/>
    <link href="favicon.png" rel="shortcut icon">
    {$this->header_links()}
    {$s['extend']}
  </head>
  <body>
    {$db_check}
E;
	}
	
	function header_ajax(){
	    return <<<E
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>VKBK</title>
    <base href="/"/>
    <link href="favicon.png" rel="shortcut icon">
    {$this->header_links()}
  </head>
  <body style="padding-top:0;">
E;
	}
	
	function header_links(){
	    return <<<E
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/jquery.fancybox3.min.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="css/perfect-scrollbar.min.css?v=0.6.11" type="text/css" media="screen" />
    <link rel="stylesheet" href="css/bootstrap-select.min.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="css/bootstrap2-toggle.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="jplayer/skin/vkbk/css/jplayer.vkbk.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="css/justifiedGallery.min.css" type="text/css" media="screen" />
    <!-- Custom styles for this template -->
    <link href="css/custom.css" rel="stylesheet">
    <link href="css/fontawesome-all.min.css" rel="stylesheet">
E;
	}
	
	function footer($s){
	    global $cfg;
		return <<<E
    </div> <!-- pj-content end -->
    <a class="gvplayer" data-fancybox="iframe" data-type="iframe" href="javascript:;"></a>
    <footer class="footer">
      <div class="container">
	<div class="row">
        <div class="col-sm-8"><p class="text-muted"><i class="fab fa-vk"></i>BK {$cfg['version']} &copy; 2016 - 2018 Megumin</p></div>
	<div class="col-sm-4" style="text-align:right;"><a href="about.php" data-pjax><i class="fa fa-code-branch"></i> История версий</a></div>
	</div>
      </div>
    </footer>
    {$this->footer_links()}
    {$s['extend']}
  </body>
</html>
E;
	}
	
	function footer_ajax(){
	    return <<<E
    {$this->footer_links()}
  </body>
</html>
E;
	}
	
	function footer_links(){
	    return <<<E
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script type="text/javascript" src="js/popper.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/jquery.pjax.js"></script>
    <script type="text/javascript" src="js/freewall.js"></script>
    <script type="text/javascript" src="js/jquery.jscroll.js"></script>
    <script type="text/javascript" src="js/jquery.fancybox3.min.js"></script>
    <script type="text/javascript" src="js/perfect-scrollbar.jquery.min.js?v=0.6.11"></script>
    <script type="text/javascript" src="js/bootstrap-select.min.js"></script>
    <script type="text/javascript" src="js/bootstrap2-toggle.min.js"></script>
    <script type="text/javascript" src="js/jquery.visible.min.js"></script>
    <script type="text/javascript" src="js/jquery.debounce.min.js"></script>
    <script type="text/javascript" src="jplayer/jquery.jplayer.min.js"></script>
    <script type="text/javascript" src="jplayer/jplayer.playlist.js"></script>
    <script type="text/javascript" src="js/hashnav.js"></script>
    <script type="text/javascript" src="js/js.cookie.min.js"></script>
	<script type="text/javascript" src="js/jquery.justifiedGallery.min.js"></script>
    <script type="text/javascript" src="js/vkbk.js"></script>
E;
	}
	
	function navigation($s){
		return <<<E
    <nav class="navbar navbar-expand-md navbar-inverse fixed-top">

          <a class="navbar-brand" href="index.php"><i class="fab fa-vk"></i>BK</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbar">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Главная">
		<a class="nav-link" href="index.php" data-pjax><i class="fa fa-home"></i><span class="xs-show">Главная</span></a></li>
            <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Альбомы">
		<a class="nav-link" href="albums.php" data-pjax><i class="fa fa-camera-retro"></i><span class="xs-show">Альбомы</span> <span class="badge badge-sup">{$s['album']}</span></a></li>
            <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Музыка">
		<a class="nav-link" href="music.php" data-pjax><i class="fa fa-music"></i><span class="xs-show">Музыка</span> <span class="badge badge-sup">{$s['music']}</span></a></li>
            <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Видео">
		<a class="nav-link" href="videos.php" data-pjax><i class="fa fa-film"></i><span class="xs-show">Видео</span> <span class="badge badge-sup">{$s['video']}</span></a></li>
	    <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Сообщения">
		<a class="nav-link" href="wall.php" data-pjax><i class="far fa-comments"></i><span class="xs-show">Сообщения</span> <span class="badge badge-sup">{$s['wall']}</span></a></li>
	    <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Документы">
		<a class="nav-link" href="docs.php" data-pjax><i class="far fa-file"></i><span class="xs-show">Документы</span> <span class="badge badge-sup">{$s['docs']}</span></a></li>
	    <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Очередь закачки">
		<a class="nav-link" href="queue.php" data-pjax><i class="fa fa-cloud-download-alt"></i><span class="xs-show">Очередь закачки</span></a></li>

	    <li class="nav-item tip" data-placement="bottom" data-toggle="tooltip" data-original-title="Настройки">
		<a class="nav-link" data-morphing id="morphing" data-src="ajax/settings.php" href="javascript:;"><i class="fa fa-sliders-h"></i><span class="xs-show">Настройки</span></a></li>
        </ul>
      </div>
    </nav>
    <div id="pj-content">
E;
	}
	
	/*
	    Function: albums_header
	    Returns album name if specified or default
	    In:
	    header - album name
	*/
	function albums_header($header){
	    return '<span class="nav-link ">'.(!empty($header) ? '<i class="far fa-folder-open"></i> '.$header : '<i class="fa fa-image"></i> Последние фотографии').'</span>';
	}
	
	/*
	    Function: queue_progress_bar
	    Returns a progress bar for specific type of data
	    In:
	    bar - progress bar data (array)
	*/
	function queue_progress_bar($bar){
	    if(isset($bar['per'])  && $bar['per']  < 0){ $bar['per']  = 0; }
	    if(isset($bar['perx']) && $bar['perx'] < 0){ $bar['perx'] = 0; }
return <<<E
<div class="col-sm-4">
<div class="row">
<div class="col-sm-6 col-md-2"><i class="fa fa-{$bar['fa']}"></i></div>
<div class="col-sm-6 col-md-10">
<div class="progress" style="height: 20px;">
	<div class="progress-bar bg-{$bar['bar']}" role="progressbar" aria-valuenow="{$bar['per']}" aria-valuemin="0" aria-valuemax="100" style="width:{$bar['per']}%">{$bar['name']} &mdash; {$bar['perx']}%</div>
</div>
</div>
</div>
</div>
E;
	}
	
	/*
	    Function: queue_list_attach
	    Returns a list of attach items to download list
	    In:
	    row - data
	    first - auto button switch (bool)
	*/
	function queue_list_attach($row,$first){
	    global $skin;
	    
	    if(isset($row['date'])){ $row['date'] = date("Y-m-d H:i:s",$row['date']); } else { $row['date'] = ' -//- '; }
	    $uri_name = $row['uri'];
	    
	    $t = '';
	    if($row['type'] == 'photo'){    $t = 'atph';$row['id'] = $row['attach_id']; }
	    if($row['type'] == 'video'){    $t = 'atvi';$row['id'] = $row['attach_id']; }
	    if($row['type'] == 'link'){     $t = 'atli';$row['id'] = $row['attach_id']; }
	    if($row['type'] == 'audio'){
		$t = 'atau';$row['id'] = $row['attach_id'];
		mb_internal_encoding("UTF-8");
		if(mb_strlen($row['title'])   > 50){ $row['title']   = mb_substr($row['title'],0,50).'...'; }
		if(mb_strlen($row['caption']) > 50){ $row['caption'] = mb_substr($row['caption'],0,50).'...'; }
		$duration = $skin->seconds2human($row['duration']);
		$uri_name = "[{$duration}] {$row['caption']} - {$row['title']}";
	    }
	    if($row['type'] == 'groups'){   $t = 'gr'; $row['uri']  = $row['photo_uri']; }
	    if($row['type'] == 'profiles'){ $t = 'pr'; $row['uri']  = $row['photo_uri']; }
	    if($row['type'] == 'doc'){      $t = 'atdc';$row['id']  = $row['attach_id']; }
	    
	    
	    // Add a autodownload for the first element in list
	    if($first == true){
		$auto = "&nbsp;&nbsp;<a href=\"queue.php?t={$t}&id={$row['id']}".(isset($row['owner_id']) ? '&oid='.$row['owner_id'] : '' )."&auto=1\" style=\"font-size:130%;\" class=\"label label-default\" onClick=\"jQuery('#{$row['id']}').hide();return true;\" title=\"Скачать автоматически\"><b class=\"fa fa-sync\"></b></a>";
	    } else { $auto = ''; }
	    $oid = isset($row['owner_id']) ? "&oid=".$row['owner_id'] : '';
return <<<E
<tr>
  <td>{$row['id']}</td>
  <td><a href="{$row['uri']}" target="_blank">{$uri_name}</a></td>
  <td>{$row['date']}</td>
  <td style="text-align:center;"><a href="queue.php?t={$t}&id={$row['id']}{$oid}" style="font-size:130%;" class="label label-default" id="{$row['id']}" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fas fa-download"></b></a>{$auto}</td>
</tr>
E;
	}
	
	/*
	    Function: Reload
	    Add's an element with countdown. After countdown redirect to specified url.
	    In:
	    class   - name of bootstrap class (string)
	    msg     - message to display      (string)
	    uri     - URL to redirect         (string)
	    timeout - time in ms              (int)
	*/
	function reload($class,$msg,$uri,$timeout){
	    if($timeout <= 0){ $timeout = 10000; } else { $timeout = $timeout * 1000; } // Default 10 sec
	    if($class=='info'){ $c = 'class="alert alert-info" role="alert"'; }
	    if($class=='warning'){ $c = 'class="alert alert-warning" role="alert"'; }
return <<<E
<tr>
  <td>
    <div {$c}><i class="fa fa-sync fa-spin"></i> {$msg}</div>
	<script type="text/javascript">
	var count = {$timeout}/1000;
	var counter = setInterval(timer, 1000);
	function timer() {
	    count=count-1;
	    if(count <= 0) {
		clearInterval(counter);
		//return;
		window.location = "{$uri}";
	    }
	    document.getElementById("gcd").innerHTML = count;
	}
	</script>
  </td>
</tr>
E;
	}
	
	/*
	    Function: details_row
	    Returns a row for video details
	    In:
	    left - data
	    right - data
	*/
	function details_row($left,$right){
return <<<E
<div class="row">
    <div class="col-sm-8">{$left}</div>
    <div class="col-sm-4">{$right}</div>
</div>
E;
	}
	
	function reload_manual($class,$msg,$uri,$timeout){
	    if($class=='info'){ $c = 'class="alert alert-info" role="alert"'; }
	    if($class=='warning'){ $c = 'class="alert alert-warning" role="alert"'; }
return <<<E
<tr>
  <td>
    <div {$c}>{$msg}<br/><a href="{$uri}">Далее...</a></div>
  </td>
</tr>
E;
	}
	
	function seconds2human($ss) {
	    $s = $ss%60;
	    $s = str_pad($s, 2, '0', STR_PAD_LEFT);
	    $m = floor(($ss%3600)/60);
	    $m = str_pad($m, 2, '0', STR_PAD_LEFT);
	    $h = floor(($ss%86400)/3600);
	    //$h = str_pad($h, 2, '0', STR_PAD_LEFT); // Double zeroes for hours? No, tnx
	    if($h > 0){
		return "$h:$m:$s";
	    } else {
		return "$m:$s";
	    }
	}

	function check_db_ver(){
	    global $db, $cfg;
	    $row = $db->query_row("SELECT val as version FROM vk_status WHERE `key` = 'version'");
	    return ($cfg['version_db'] != $row['version']) ? false : true;
	}
}

?>