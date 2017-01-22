<?php

class skin {
	
	function skin(){
		return true;
	}
	
	function header($s){
	    global $cfg;
	    $db_check = $this->check_db_ver() ? '' : '<div class="label label-danger db-expired"><i class="fa fa-warning"></i> Структура базы данных устарела. Обратитесь к <a href="update/index.html" target="_blank">инструкции</a> по обновлению.</div>';
		return <<<E
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>VKBK</title>
    <link href="favicon.png" rel="shortcut icon">
    <base href="{$cfg['vkbk_url']}"/>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/custom.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    {$s['extend']}
  </head>
  <body>
    {$db_check}
E;
	}
	
	function footer($s){
	    global $cfg;
		return <<<E
    <footer class="footer">
      <div class="container">
        <p class="text-muted"><i class="fa fa-vk" style="font-size:18px;"></i>BK {$cfg['version']} &copy; 2016 - 2017 Megumin</p>
      </div>
    </footer>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    {$s['extend']}
  </body>
</html>
E;
	}
	
	function navigation($s){
		return <<<E
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php"><i class="fa fa-vk" style="font-size:22px;"></i>BK</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
	    <li><a href="index.php"><i class="fa fa-home"></i></a></li>
            <li><a href="albums.php"><i class="fa fa-camera"></i>Альбомы <span class="badge">{$s['album']}</span></a></li>
            <li><a href="music.php"><i class="fa fa-music"></i>Музыка <span class="badge">{$s['music']}</span></a></li>
            <li><a href="videos.php"><i class="fa fa-film"></i>Видео <span class="badge">{$s['video']}</span></a></li>
	    <li><a href="wall.php"><i class="fa fa-comments-o"></i>Сообщения <span class="badge">{$s['wall']}</span></a></li>
	    <li><a href="docs.php"><i class="fa fa-file-o"></i>Документы <span class="badge">{$s['docs']}</span></a></li>
            <li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-cogs"></i>Панель управления</a>
                <ul class="dropdown-menu">
		  
		  <li><a href="queue.php"><i class="fa fa-cloud-download"></i> Очередь закачки</a></li>
		  <li role="separator" class="divider"></li>
                  <li><a href="settings.php"><i class="fa fa-sliders"></i> Настройки</a></li>
		  <li role="separator" class="divider"></li>
		  <li><a href="about.php"><i class="fa fa-code-fork"></i> История версий</a></li>
                </ul>
	    </li>
          </ul>
        </div>
      </div>
    </nav>
E;
	}
	
	function queue_progress_bar($bar){
	    if(isset($bar['per'])  && $bar['per']  < 0){ $bar['per']  = 0; }
	    if(isset($bar['perx']) && $bar['perx'] < 0){ $bar['perx'] = 0; }
return <<<E
<div class="row col-sm-6">
<div class="col-sm-6 col-md-5 col-lg-4"><i class="fa fa-{$bar['fa']}"></i> {$bar['name']} <span class="label label-default">{$bar['perx']}%</span></div>
<div class="col-sm-6 col-md-7 col-lg-8">
<div class="progress">
	<div class="progress-bar progress-bar-{$bar['bar']}" role="progressbar" aria-valuenow="{$bar['per']}" aria-valuemin="0" aria-valuemax="100" style="width:{$bar['per']}%"><span class="sr-only">{$bar['per']}% Complete</span></div>
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
	    fiirst - aqto button switch (bool)
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
	    
	    
	    // Add a autodownload for the first element in list
	    if($first == true){
		$auto = "&nbsp;&nbsp;<a href=\"queue.php?t={$t}&id={$row['id']}&auto=1\" style=\"font-size:130%;\" class=\"label label-default\" onClick=\"jQuery('#{$row['id']}').hide();return true;\" title=\"Скачать автоматически\"><b class=\"fa fa-repeat\"></b></a>";
	    } else { $auto = ''; }
return <<<E
<tr>
  <td>{$row['id']}</td>
  <td><a href="{$row['uri']}" target="_blank">{$uri_name}</a></td>
  <td>{$row['date']}</td>
  <td style="text-align:center;"><a href="queue.php?t={$t}&id={$row['id']}" style="font-size:130%;" class="label label-default" id="{$row['id']}" onClick="jQuery('#{$row['id']}').hide();return true;" title="Скачать"><b class="fa fa-arrow-circle-up"></b></a>{$auto}</td>
</tr>
E;
	}
	
	function reload($class,$msg,$uri,$timeout){
	    if($timeout <= 0){ $timeout = 10000; } else { $timeout = $timeout * 1000; }
	    if($class=='info'){ $c = 'class="alert alert-info" role="alert"'; }
	    if($class=='warning'){ $c = 'class="alert alert-warning" role="alert"'; }
return <<<E
<tr>
  <td>
    <div {$c}><i class="fa fa-refresh fa-spin"></i> {$msg}</div>
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