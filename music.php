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

print $skin->header(array());
print $skin->navigation($lc);

print <<<E
<div class="container">
          <h2 class="sub-header"><i class="fa fa-music"></i> Музыка</h2>
		  <div class="ajs-player">
		  <div class="col-sm-6" style="text-align:center;font-size:90%;padding:10px;">
			<strong>Сортировка:</strong> ВК&nbsp;
			<a href="#" onClick="sort('date_added','desc'); return false;"><i class="fa fa-arrow-up"></i></a>
			&nbsp;
			<a href="#" onClick="sort('date_added','asc'); return false;"><i class="fa fa-arrow-down"></i></a>
			&nbsp;&nbsp;
			исполнитель&nbsp;
			<a href="#" onClick="sort('artist','asc'); return false;"><i class="fa fa-arrow-up"></i></a>
			&nbsp;
			<a href="#" onClick="sort('artist','desc'); return false;"><i class="fa fa-arrow-down"></i></a>
			&nbsp;&nbsp;
			время&nbsp;
			<a href="#" onClick="sort('time','asc'); return false;"><i class="fa fa-arrow-up"></i></a>
			&nbsp;
			<a href="#" onClick="sort('time','desc'); return false;"><i class="fa fa-arrow-down"></i></a>
		  </div>
		  <div class="col-sm-6">
		  <audio preload></audio>
		  </div>
		  </div>
          <div class="table-responsive ajs">
            <ol id="music-container">
E;

$r = $db->query("SELECT * FROM vk_music ORDER BY date_added DESC");
while($list = $db->return_row($r)){
	// Rewrite if you plan to store content outside of web directory and will call it by Alias
	//if(substr($list['path'],0,4) != 'http'){
		//$list['path'] = preg_replace("/^\/VKBK\/music\//","/vkbk-music/",$list['path']);
	//}
	$sort['artist'] = preg_replace("/[\"\&]/","",$list['artist']);
	$sort['time'] = preg_replace("/[\"\&]/","",$list['duration']);
	$list['duration'] = $skin->seconds2human($list['duration']);
print <<<E
<li data-dadded="{$list['date_added']}" data-dsync="{$list['date_done']}" data-artist="{$sort['artist']}" data-time="{$sort['time']}">
    <span class="badge">{$list['duration']}</span> <a href="#" data-src="{$list['path']}">{$list['artist']} - {$list['title']}</a>
</li>
E;
}

print <<<E
            </ol>
          </div>
</div>
E;

$ex_bot = <<<E
<script type="text/javascript" src="audiojs/audio.min.js"></script>
<script type="text/javascript">
      $(function() { 
        // Setup the player to autoplay the next track
        var a = audiojs.createAll({
          trackEnded: function() {
            var next = $('ol li.playing').next();
            if (!next.length) next = $('ol li').first();
            next.addClass('playing').siblings().removeClass('playing');
            audio.load($('a', next).attr('data-src'));
            audio.play();
          }
        });
        
        // Load in the first track
        var audio = a[0];
            first = $('ol a').attr('data-src');
        $('ol li').first().addClass('playing');
        audio.load(first);

        // Load in a track on click
        $('ol li').click(function(e) {
          e.preventDefault();
          $(this).addClass('playing').siblings().removeClass('playing');
          audio.load($('a', this).attr('data-src'));
          audio.play();
        });
        // Keyboard shortcuts
        $(document).keydown(function(e) {
          var unicode = e.charCode ? e.charCode : e.keyCode;
             // right arrow
          if (unicode == 39) {
            var next = $('li.playing').next();
            if (!next.length) next = $('ol li').first();
            next.click();
            // back arrow
          } else if (unicode == 37) {
            var prev = $('li.playing').prev();
            if (!prev.length) prev = $('ol li').last();
            prev.click();
            // spacebar
          } else if (unicode == 32) {
            audio.playPause();
          }
        })
      });
	  
	  // Sorting options
	  var c = '';
	  var d = '';
	  function sort(method,e){
		if(method == 'date_added'){
		if(e == 'asc'){ c = e; d = 'dadded'; $("#music-container li").sort(sort_li).appendTo('#music-container'); }
		if(e == 'desc'){ c = e; d = 'dadded'; $("#music-container li").sort(sort_li).appendTo('#music-container'); }
		}
		if(method == 'artist'){
		if(e == 'asc'){ c = e; d = 'artist'; $("#music-container li").sort(sort_li).appendTo('#music-container'); }
		if(e == 'desc'){ c = e; d = 'artist'; $("#music-container li").sort(sort_li).appendTo('#music-container'); }
		}
		if(method == 'time'){
		if(e == 'asc'){ c = e; d = 'time'; $("#music-container li").sort(sort_li).appendTo('#music-container'); }
		if(e == 'desc'){ c = e; d = 'time'; $("#music-container li").sort(sort_li).appendTo('#music-container'); }
		}
	  }
	  // sort function callback
	  function sort_li(a, b){
	    if(c == 'asc'){ return ($(b).data(d)) < ($(a).data(d)) ? 1 : -1; }
		if(c == 'desc'){ return ($(b).data(d)) > ($(a).data(d)) ? 1 : -1; }
	  }
</script>

E;
print $skin->footer(array(
	'v'=>$version,
	'extend'=> $ex_bot,
));

$db->close($res);

?>