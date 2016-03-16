<?php

class skin {
	
	function skin(){
		return true;
	}
	
	function header($s){
		return <<<E
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>VKBK</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/custom.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    {$s['extend']}
  </head>
  <body>
E;
	}
	
	function footer($s){
		return <<<E
    <footer class="footer">
      <div class="container">
        <p class="text-muted">VKBK {$s['v']} &copy; 2016 Megumin</p>
      </div>
    </footer>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="js/jquery-1.9.1.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
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
          <a class="navbar-brand" href="index.php">VKBK</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="index.php"><i class="fa fa-cogs"></i>Панель управления</a></li>
            <li><a href="albums.php"><i class="fa fa-camera"></i>Альбомы <span class="badge">{$s['album']}</span></a></li>
            <li><a href="music.php"><i class="fa fa-music"></i>Музыка <span class="badge">{$s['music']}</span></a></li>
            <li><a href="videos.php"><i class="fa fa-film"></i>Видео <span class="badge">{$s['video']}</span></a></li>
          </ul>
        </div>
      </div>
    </nav>
E;
	}
	
	function reload($class,$msg,$uri,$timeout){
	    if($class=='info'){ $c = 'class="alert alert-info" role="alert"'; }
	    if($class=='warning'){ $c = 'class="alert alert-warning" role="alert"'; }
return <<<E
<tr>
  <td>
    <div {$c}><i class="fa fa-refresh fa-spin"></i> {$msg}</div>
	<script type="text/javascript">
	window.setTimeout(function(){ window.location = "{$uri}"; },{$timeout});
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

}

?>