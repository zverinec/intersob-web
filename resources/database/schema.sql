SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `page`;
CREATE TABLE `page` (
  `id_page` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_year` int(10) unsigned NOT NULL,
  `heading` tinytext NOT NULL,
  `url` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `description` varchar(255) NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `icon` tinytext NOT NULL,
  PRIMARY KEY (`id_page`),
  UNIQUE KEY `url_id_year` (`url`,`id_year`),
  KEY `id_year` (`id_year`),
  CONSTRAINT `page_ibfk_1` FOREIGN KEY (`id_year`) REFERENCES `year` (`id_year`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

DROP TABLE IF EXISTS `team`;
CREATE TABLE `team` (
  `id_team` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_year` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` tinytext NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `inserted` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_team`),
  UNIQUE KEY `id_year_name` (`id_year`,`name`),
  CONSTRAINT `team_ibfk_1` FOREIGN KEY (`id_year`) REFERENCES `year` (`id_year`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

DROP TABLE IF EXISTS `team_member`;
CREATE TABLE `team_member` (
  `id_team_member` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_team` int(10) unsigned NOT NULL,
  `name` tinytext NOT NULL,
  `age` varchar(3) NOT NULL,
  `school` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  PRIMARY KEY (`id_team_member`),
  KEY `id_team` (`id_team`),
  CONSTRAINT `team_member_ibfk_1` FOREIGN KEY (`id_team`) REFERENCES `team` (`id_team`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id_user` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `inserted` datetime NOT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `nickname` (`nickname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `user` (`id_user`, `nickname`, `password`, `inserted`) VALUES
(1,	'admin',	'3ab0764dd7247e19460cb54f917f5a47d5c426d8b343c04013501ecad88f05b9',	'2017-01-12 16:48:01');

DROP TABLE IF EXISTS `year`;
CREATE TABLE `year` (
  `id_year` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date` date NOT NULL,
  `reg_open` datetime NOT NULL,
  `reg_closed` datetime NOT NULL,
  `menu1` text NOT NULL,
  `menu2` text NOT NULL,
  `content` text NOT NULL,
  `color` varchar(30) NOT NULL,
  `info_embargo` datetime NOT NULL,
  PRIMARY KEY (`id_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

