<?php

// Defines
define(ROOT,dirname(__FILE__).'/');

// Configuration
$cfg = array();

// VKBK
// Example: http://your.host/
$cfg['vkbk_url'] = '';

// MySQL Database
$cfg['host'] = 'localhost';
$cfg['user'] = '';
$cfg['pass'] = '';
$cfg['base'] = '';

// VK.API Information
// App ID (ID приложения)
$cfg['vk_id'] = ;
// Protected key (Защищенный ключ)
$cfg['vk_secret'] = '';
// Example: http://your.host/
$cfg['vk_uri'] = '';

// Path for download
/*
For windows users if you want to store files on different drives
you should add an Alias for your Vhost configuration and uncomment
some lines in: queue.php, ajax/*-paginator.php, albums.php, videos.php

Vhost alias example:
Alias "/vkbk-photo" "C:/VKBK/photo"
Alias "/vkbk-music" "D:/VKBK/music"
Alias "/vkbk-video" "E:/VKBK/video"
*/
// Example: /VKBK/photo/
$cfg['photo_path'] = '';
// Example: /VKBK/music/
$cfg['music_path'] = '';
// Example: /VKBK/video/
$cfg['video_path'] = '';

// Albums
$cfg['photo_layout_width'] = 300;
$cfg['perpage_photo'] = 24;

// Videos
$cfg['perpage_video'] = 24;


?>