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

print <<<E
<div class="container">
          <h2 class="sub-header">VKBK история версий</h2>
		  <div class="well">
			<p>VKBK - это инструмент для создания и синхронизации локального бэкапа вашего лампового профиля ВК.</p>
			<p>Сторонние библиотеки:<br/>
			<a href="http://getbootstrap.com/">Bootstrap</a>
				<span class="label label-default">3.3.6</span>
			<a href="https://github.com/vladkens/VK">PHP класс для VK.API</a>
				<span class="label label-default">0.1.7</span>
			<a href="https://github.com/kombai/freewall">Freewall.js</a>
				<span class="label label-default">1.05</span>
			<a href="https://github.com/pklauzinski/jscroll">jScroll.js</a>
				<span class="label label-default">2.3.4</span>
			<a href="https://github.com/fancyapps/fancyBox">fancybox.js</a>
				<span class="label label-default">2.1.5</span>
			<a href="https://fortawesome.github.io/Font-Awesome/">Font Awesome</a>
				<span class="label label-default">4.5.0</span>
			<a href="https://github.com/kolber/audiojs">audio.js</a>
				<span class="label label-default">14 Mar 2016</span>
			</p>
		  </div>
          <div class="container">

<h4>ToDo</h4>
<ul class="list-group list-unstyled">
<li><label><span class="label label-default">сделать</span></label> синхронизация > диалоги</li>
<li><label><span class="label label-default">сделать</span></label> синхронизация > стена</li>
</ul>
<h4>версия 0.3</h4>
<ul class="list-group list-unstyled"><div>2016-04-21</div>
<li><label><span class="label label-warning">обновлено</span></label> Обновление названий альбомов при синхронизации.</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-27</div>
<li><label><span class="label label-danger">багфикс</span></label> Исправлен баг при синхронизации если альбом пустой.</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-21</div>
<li><label><span class="label label-warning">обновлено</span></label> Код немного приведен в порядок для работы на PHP 5.3.x.</li>
<li><label><span class="label label-danger">багфикс</span></label> Неправильные флаги при повторной синхронизации музыки.</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-19</div>
<li><label><span class="label label-warning">обновлено</span></label> Настройки таймаутов перенесены в конфиг.</li>
<li><label><span class="label label-warning">обновлено</span></label> Обновление количества фото в альбомах при синхронизации.</li>
<li><label><span class="label label-warning">обновлено</span></label> Не отображалась кнопка автоматического скачивания.</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-15</div>
<li><label><span class="label label-primary">новое</span></label> Просмотр видеозаписей</li>
<li><label><span class="label label-warning">обновлено</span></label> При ошибке синхронизации в автоматическом режиме и html ответе сервера (прим. ошибка Bad Gateway) добавлен пропуск данных ID.</li>
<li><label><span class="label label-primary">новое</span></label> Добавлена синхронизация видеозаписей</li>
<li><label><span class="label label-danger">багфикс</span></label> Исправлено отображение смешенного контента в очереди закачек.</li>
</ul>
<h4>версия 0.2</h4>
<ul class="list-group list-unstyled"><div>2016-03-14</div>
<li><label><span class="label label-primary">новое</span></label> Добавлен плеер в раздел музыки (audio.js)</li>
<li><label><span class="label label-danger">багфикс</span></label> Добавлена проверка на содержимое ответа сервера.</li>
<li><label><span class="label label-danger">багфикс</span></label> Некоторые кириллические названия треков не сохранялись в базу.</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-13</div>
<li><label><span class="label label-primary">новое</span></label> Добавлены иконки (FontAwesome)</li>
<li><label><span class="label label-info">инфо</span></label> Имена аудиозаписей чистятся от не ASCII символов, так как PHP для Windows более чем точно будет скомпилирован без поддержки юникода.</li>
<li><label><span class="label label-danger">баг</span></label> Непонятно почему ВК не отдает некоторые аудиозаписи через API, хотя они не являются заблокированными.</li>
<li><label><span class="label label-warning">обновлено</span></label> Обновлен блок и страница очереди закачки. Объединены фотографии и аудиозаписи.</li>
<li><label><span class="label label-primary">новое</span></label> Синхронизация аудиозаписей</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-12</div>
<li><label><span class="label label-warning">обновлено</span></label> Раздел Альбомы по-умолчанию отображают 25 последних добавленных фотографий</li>
<li><label><span class="label label-danger">багфикс</span></label> Добавлена проверка на размер файла при сохранении</li>
</ul>
<h4>версия 0.1</h4>
<ul class="list-group list-unstyled"><div>2016-03-09</div>
<li><label><span class="label label-primary">новое</span></label> Просмотр фотографий (Fancyfox)</li>
<li><label><span class="label label-danger">баг</span></label> Необходима проверка на размер файла при закачке</li>
<li><label><span class="label label-warning">обновлено</span></label> Ссылку на очередь отображается если есть хотя бы один элемент</li>
<li><label><span class="label label-primary">новое</span></label> Подгрузка фотографий при скроллинге (jscroll.js)</li>
<li><label><span class="label label-primary">новое</span></label> Responsive макет для фотографий (freewall.js)</li>
<li><label><span class="label label-primary">новое</span></label> Отображение фотографий в альбомах</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-08</div>
<li><label><span class="label label-primary">новое</span></label> Получение и сохранение фото</li>
<li><label><span class="label label-primary">новое</span></label> Очередь закачки</li>
<li><label><span class="label label-warning">обновлено</span></label> Блок очереди закачки в панели управления</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-07</div>
<li><label><span class="label label-success">функционал</span></label> Синхронизация фотографий</li>
<li><label><span class="label label-danger">багфикс</span></label> Исправлены баги в синхронизации альбомов</li>
</ul>
<ul class="list-group list-unstyled"><div>2016-03-06</div>
<li><label><span class="label label-success">функционал</span></label> Синхронизация альбомов</li>
<li><label><span class="label label-success">функционал</span></label> Авторизация</li>
<li><label><span class="label label-primary">новое</span></label> Интерфейс, структура</li>
<li><label><span class="label label-info">инфо</span></label> Начало разработки</li>
</ul>

          </div>
</div>
E;

print $skin->footer(array('v'=>$version,'extend'=>''));

$db->close($res);

?>