-- phpMyAdmin SQL Dump
-- version 3.4.7
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Nov 21, 2016 at 11:03 PM
-- Server version: 5.1.59
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `vkbk`
--

-- --------------------------------------------------------

--
-- Table structure for table `vk_albums`
--

CREATE TABLE IF NOT EXISTS `vk_albums` (
  `id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `updated` int(10) unsigned NOT NULL,
  `img_total` int(10) unsigned NOT NULL,
  `img_done` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `updated` (`updated`),
  KEY `images` (`img_total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `vk_albums`
--

INSERT INTO `vk_albums` (`id`, `name`, `created`, `updated`, `img_total`, `img_done`) VALUES
(-9000, 'Системный альбом', 1457276070, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vk_attach`
--

CREATE TABLE IF NOT EXISTS `vk_attach` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `wall_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `is_local` tinyint(1) NOT NULL,
  `attach_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `uri` text NOT NULL,
  `path` varchar(255) NOT NULL,
  `width` smallint(5) unsigned NOT NULL,
  `height` smallint(5) unsigned NOT NULL,
  `text` text NOT NULL,
  `date` int(11) NOT NULL,
  `access_key` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `duration` int(11) NOT NULL,
  `player` text NOT NULL,
  `link_url` text NOT NULL,
  `caption` varchar(255) NOT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `uniqid` (`wall_id`,`attach_id`),
  KEY `local` (`is_local`),
  KEY `width` (`width`),
  KEY `height` (`height`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `vk_counters`
--

CREATE TABLE IF NOT EXISTS `vk_counters` (
  `album` mediumint(8) unsigned NOT NULL,
  `photo` mediumint(8) unsigned NOT NULL,
  `music` mediumint(8) unsigned NOT NULL,
  `video` mediumint(8) unsigned NOT NULL,
  `wall` mediumint(8) unsigned NOT NULL,
  `docs` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `counters` (`album`,`photo`,`music`,`video`,`wall`,`docs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `vk_counters`
--

INSERT INTO `vk_counters` (`album`, `photo`, `music`, `video`, `wall`, `docs`) VALUES
(0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vk_docs`
--

CREATE TABLE IF NOT EXISTS `vk_docs` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `title` text NOT NULL,
  `size` int(11) NOT NULL,
  `ext` varchar(25) NOT NULL,
  `uri` text NOT NULL,
  `date` int(11) NOT NULL,
  `type` smallint(6) NOT NULL,
  `preview_uri` text NOT NULL,
  `preview_path` text NOT NULL,
  `width` smallint(5) unsigned NOT NULL,
  `height` smallint(5) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `in_queue` tinyint(1) NOT NULL,
  `local_path` text NOT NULL,
  `local_size` int(11) NOT NULL,
  `local_w` smallint(6) NOT NULL,
  `local_h` smallint(6) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `deleted` (`deleted`),
  KEY `queue` (`in_queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vk_groups`
--

CREATE TABLE IF NOT EXISTS `vk_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nick` varchar(255) NOT NULL,
  `photo_uri` text NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vk_music`
--

CREATE TABLE IF NOT EXISTS `vk_music` (
  `id` int(10) unsigned NOT NULL,
  `artist` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `album` int(10) unsigned NOT NULL,
  `duration` smallint(5) unsigned NOT NULL,
  `uri` text NOT NULL,
  `date_added` int(10) unsigned NOT NULL,
  `date_done` int(10) unsigned NOT NULL,
  `saved` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `path` text NOT NULL,
  `hash` varchar(40) NOT NULL,
  `in_queue` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `queue` (`in_queue`),
  KEY `d_added` (`date_added`),
  KEY `d_saved` (`date_done`),
  KEY `saved` (`saved`),
  KEY `deleted` (`deleted`),
  KEY `track` (`artist`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vk_music_albums`
--

CREATE TABLE IF NOT EXISTS `vk_music_albums` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vk_photos`
--

CREATE TABLE IF NOT EXISTS `vk_photos` (
  `id` int(11) NOT NULL,
  `album_id` int(10) NOT NULL,
  `date_added` int(10) unsigned NOT NULL,
  `uri` text NOT NULL,
  `width` smallint(5) unsigned NOT NULL,
  `height` smallint(5) unsigned NOT NULL,
  `date_done` int(11) unsigned NOT NULL,
  `saved` tinyint(1) NOT NULL,
  `path` text NOT NULL,
  `hash` varchar(40) NOT NULL,
  `in_queue` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `queue` (`in_queue`),
  KEY `album` (`album_id`),
  KEY `width` (`width`),
  KEY `height` (`height`),
  KEY `dsaved` (`date_done`),
  KEY `saved` (`saved`),
  KEY `dadded` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vk_profiles`
--

CREATE TABLE IF NOT EXISTS `vk_profiles` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `sex` tinyint(1) NOT NULL DEFAULT '0',
  `nick` varchar(255) NOT NULL,
  `photo_uri` text NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vk_session`
--

CREATE TABLE IF NOT EXISTS `vk_session` (
  `vk_id` int(10) unsigned NOT NULL,
  `vk_token` varchar(255) NOT NULL,
  `vk_expire` int(11) NOT NULL,
  `vk_user` int(11) NOT NULL,
  PRIMARY KEY (`vk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `vk_session`
--

INSERT INTO `vk_session` (`vk_id`, `vk_token`, `vk_expire`, `vk_user`) VALUES
(1, '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vk_status`
--

CREATE TABLE IF NOT EXISTS `vk_status` (
  `key` varchar(255) NOT NULL,
  `val` text NOT NULL,
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `vk_status`
--

INSERT INTO `vk_status` (`key`, `val`) VALUES
('log_docs', ''),
('log_music', ''),
('log_photo', ''),
('log_video', ''),
('version', '2017031201'),
('auto-queue-audio', '0'),
('auto-queue-photo', '0'),
('play-local-video', '0');

-- --------------------------------------------------------

--
-- Table structure for table `vk_videos`
--

CREATE TABLE IF NOT EXISTS `vk_videos` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `desc` text NOT NULL,
  `duration` smallint(5) unsigned NOT NULL,
  `preview_uri` varchar(255) NOT NULL,
  `preview_path` varchar(255) NOT NULL,
  `player_uri` text NOT NULL,
  `access_key` varchar(255) NOT NULL,
  `date_added` int(10) unsigned NOT NULL,
  `date_done` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `in_queue` tinyint(1) NOT NULL,
  `local_path` text NOT NULL,
  `local_size` int(11) NOT NULL,
  `local_format` varchar(50) NOT NULL,
  `local_w` smallint(5) unsigned NOT NULL,
  `local_h` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dur` (`duration`),
  KEY `dadded` (`date_added`),
  KEY `dsaved` (`date_done`),
  KEY `deleted` (`deleted`),
  KEY `queue` (`in_queue`),
  KEY `local_w` (`local_w`),
  KEY `local_h` (`local_h`),
  KEY `format` (`local_format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vk_wall`
--

CREATE TABLE IF NOT EXISTS `vk_wall` (
  `id` int(11) NOT NULL,
  `from_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `post_type` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `attach` tinyint(1) NOT NULL,
  `repost` int(11) NOT NULL,
  `is_repost` tinyint(1) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `from` (`from_id`),
  KEY `owner` (`owner_id`),
  KEY `type` (`post_type`),
  KEY `attach` (`attach`),
  KEY `repost` (`is_repost`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
