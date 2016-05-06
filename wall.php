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

// Get Functions
require_once(ROOT.'classes/func.php');
$f = new func();

$row = $db->query_row("SELECT val as version FROM vk_status WHERE `key` = 'version'");
$version = $row['version'];

// Get local counters for top menu
$lc = $db->query_row("SELECT * FROM vk_counters");

$ex_top = <<<E
<link rel="stylesheet" href="/css/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
<link rel="stylesheet" href="/css/jquery.fancybox-buttons.css?v=1.0.5" type="text/css" media="screen" />
E;

print $skin->header(array('extend'=>$ex_top));
print $skin->navigation($lc);

print <<<E
<div class="container wall-body">
          <h2 class="sub-header"><i class="fa fa-comments-o"></i> Сообщения</h2>
          <div class="container" id="wall-list">
E;

	$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 0;
	$npage = $page+1;
	$offset_page = ($page > 0) ? $cfg['perpage_wall']*$page : 0;

$r = $db->query("SELECT * FROM vk_wall WHERE is_repost = 0 ORDER BY date DESC LIMIT {$offset_page},{$cfg['perpage_wall']}");
while($row = $db->return_row($r)){
	$repost_body = '';
	$rrp_body = '';
	
	// Post have a repost?
	if($row['repost'] > 0){
		$rp = $db->query_row("SELECT * FROM vk_wall WHERE id = {$row['repost']}");
		// Post have a rerepost?
		if($rp['repost'] > 0){
			$rrp = $db->query_row("SELECT * FROM vk_wall WHERE id = {$rp['repost']}");
			$rrp_body = $f->wall_show_post($rrp,true,'');
		}
		$repost_body = $f->wall_show_post($rp,true,$rrp_body);
		
	} // repost body end
		
	// Make post
	print $f->wall_show_post($row,false,$repost_body);
	
} // End of while

print <<<E
			<div class="paginator-next" style="display:none;"><a href="ajax/wall-paginator.php?page={$npage}">следующая страница</a></div>
          </div>
</div>
E;

$ex_bot = <<<E
<script type="text/javascript" src="/js/freewall.js"></script>
<script type="text/javascript" src="/js/jquery.jscroll.min.js"></script>
<script type="text/javascript" src="/js/jquery.fancybox.pack.js?v=2.1.5"></script>
<script type="text/javascript" src="/js/jquery.fancybox-buttons.js?v=1.0.5"></script>
<script type="text/javascript" src="/js/hashnav.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	// Hash URL commands
	urlCommands.bind('post', function(id) {
		if($.isNumeric(id)){
			$.fancybox.open({
				type : 'iframe',
				href : '{$cfg['vkbk_url']}ajax/wall-post.php?p='+id+'',
				title : '#'+id,
				maxWidth	: 960,
				//maxHeight	: 720,
				fitToView	: false,
				width		: '90%',
				height		: '90%',
				autoSize	: true,
				closeClick	: false,
				openEffect	: 'none',
				closeEffect	: 'none',
				
				padding : 5,
				arrows : false,
				closeBtn : true,
				nextClick : false,
				loop : false,
				helpers : {
					overlay : {
						showEarly : false,
						css : {
							'background' : 'rgba(0, 0, 0, 0.85)'
						}
					},
					title: {
						type: 'inside'
					}
				}
			});
		}
	});
	
	$(".free-wall").each(function(){
		var wall = new Freewall(this);
		
		wall.reset({
			selector: '.brick',
			animate: false,
			cellW: {$cfg['wall_layout_width']},
			cellH: 'auto',
			onResize: function() {
				wall.fitWidth();
			}
		});
		
		var images = wall.container.find('.brick');
		images.find('img').load(function() {
			wall.fitWidth();
		});
	});
	
	$('#wall-list').jscroll({
		debug:false,
		nextSelector: 'div.paginator-next > a:last',
		//autoTriggerUntil: 100,
		padding: 200,
		callback: function(){
			
			$(".jscroll-added").filter(":last").find(".free-wall").each(function(){
				var wall = new Freewall(this);
				wall.reset({
					selector: '.brick',
					animate: false,
					cellW: {$cfg['wall_layout_width']},
					cellH: 'auto',
					onResize: function() {
						wall.fitWidth();
					}
				});
				
				var images = wall.container.find('.brick');
				images.find('img').load(function() {
					wall.fitWidth();
				});
			
			});
			
			$(".full-date").tooltip();
		} // callback end
	});

	$(".fancybox").fancybox({
		padding : 5,
		arrows : false,
		closeBtn : false,
		nextClick : true,
		loop : false,
		keys : {
			toggle : [32], // space - toggle fullscreen
			play : [70]
		},
	    helpers : {
			overlay : {
				css : {
					'background' : 'rgba(0, 0, 0, 0.85)'
				}
	        },
		    buttons : {}
	    }
	});
	
	$(".wallious").fancybox({
		maxWidth	: 960,
		//maxHeight	: 720,
		fitToView	: false,
		width		: '70%',
		height		: '70%',
		autoSize	: true,
		closeClick	: false,
		openEffect	: 'none',
		closeEffect	: 'none',
		
		padding : 5,
		arrows : false,
		closeBtn : false,
		nextClick : false,
		loop : false,
	    helpers : {
			overlay : {
				showEarly : false,
				css : {
					'background' : 'rgba(0, 0, 0, 0.85)'
	            }
			},
			title: {
				type: 'inside'
			}
	    }
	});
	
	$(".various").fancybox({
		maxWidth	: 1280,
		maxHeight	: 720,
		fitToView	: false,
		width		: '70%',
		height		: '70%',
		autoSize	: false,
		closeClick	: false,
		openEffect	: 'none',
		closeEffect	: 'none',
		
		padding : 5,
		arrows : false,
		closeBtn : false,
		nextClick : false,
		loop : false,
	    helpers : {
			overlay : {
				css : {
					'background' : 'rgba(0, 0, 0, 0.85)'
	            }
			},
			title: {
				type: 'inside'
			}
	    },
		beforeLoad: function() {
            var el, id = $(this.element).data('title-id');
	
            if (id) {
                el = $('#' + id);
            
                if (el.length) {
                    this.title = el.html();
                }
            }
        }
	});
	
	$(".full-date").tooltip();
});

	function expand_desc(){
		var el = jQuery(".fancybox-title-inside-wrap");
		if(el.css("height") == '40px'){
			el.css("height","auto");
			jQuery(".fancybox-title-inside-wrap > .expander").html("свернуть");
		} else {
			el.css("height","40px");
			jQuery(".fancybox-title-inside-wrap > .expander").html("показать");
		}
	}
</script>
E;

print $skin->footer(array(
	'v' => $version,
	'extend'=> $ex_bot
));

$db->close($res);

?>