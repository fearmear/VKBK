-- phpMyAdmin SQL Dump
-- version 3.4.7
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Mar 16, 2016 at 03:19 AM
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `vk_albums`
--

INSERT INTO `vk_albums` (`id`, `name`, `created`, `updated`, `img_total`, `img_done`) VALUES
(-9000, 'Системный альбом', 1457276070, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vk_counters`
--

CREATE TABLE IF NOT EXISTS `vk_counters` (
  `album` mediumint(8) unsigned NOT NULL,
  `photo` mediumint(8) unsigned NOT NULL,
  `music` mediumint(8) unsigned NOT NULL,
  `video` mediumint(8) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `vk_counters`
--

INSERT INTO `vk_counters` (`album`, `photo`, `music`, `video`) VALUES
(0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vk_music`
--

CREATE TABLE IF NOT EXISTS `vk_music` (
  `id` int(10) unsigned NOT NULL,
  `artist` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
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
  KEY `height` (`height`)
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
('log_music', ''),
('log_photo', ''),
('log_video', ''),
('version', '0.3 beta');

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
