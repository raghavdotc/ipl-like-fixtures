-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2015 at 04:26 PM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ipl`
--
CREATE DATABASE IF NOT EXISTS `ipl` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `ipl`;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE IF NOT EXISTS `cities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `city` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `city`) VALUES
(1, 'City A'),
(2, 'City B'),
(3, 'City C'),
(4, 'City D'),
(5, 'City E'),
(6, 'City F'),
(7, 'City G'),
(8, 'City H');

-- --------------------------------------------------------

--
-- Table structure for table `fixtures`
--

CREATE TABLE IF NOT EXISTS `fixtures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `home_team_id` int(10) unsigned NOT NULL,
  `away_team_id` int(10) unsigned NOT NULL,
  `venue_id` int(10) unsigned NOT NULL,
  `winner` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fixtures_ibfk_1` (`home_team_id`),
  KEY `fixtures_ibfk_2` (`away_team_id`),
  KEY `fixtures_ibfk_3` (`venue_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

--
-- Dumping data for table `fixtures`
--

INSERT INTO `fixtures` (`id`, `title`, `home_team_id`, `away_team_id`, `venue_id`, `winner`, `date`) VALUES
(8, '', 2, 8, 2, 0, '2015-06-20'),
(9, '', 1, 6, 1, 0, '2015-06-20'),
(10, '', 5, 3, 5, 0, '2015-06-21'),
(11, '', 7, 4, 7, 0, '2015-06-21');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE IF NOT EXISTS `teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `home_city_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `teams_ibfk_1` (`home_city_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `home_city_id`) VALUES
(1, 'Team A', 1),
(2, 'Team B', 2),
(3, 'Team C', 3),
(4, 'Team D', 4),
(5, 'Team E', 5),
(6, 'Team F', 6),
(7, 'Team G', 7),
(8, 'Team H', 8);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fixtures`
--
ALTER TABLE `fixtures`
  ADD CONSTRAINT `fixtures_ibfk_1` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `fixtures_ibfk_2` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `fixtures_ibfk_3` FOREIGN KEY (`venue_id`) REFERENCES `cities` (`id`);

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`home_city_id`) REFERENCES `cities` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
