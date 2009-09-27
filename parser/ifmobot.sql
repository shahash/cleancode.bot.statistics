-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Сен 26 2009 г., 00:14
-- Версия сервера: 5.1.37
-- Версия PHP: 5.2.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `ifmobot`
--

-- --------------------------------------------------------

--
-- Структура таблицы `log_cmdcall`
--

DROP TABLE IF EXISTS `log_cmdcall`;
CREATE TABLE `log_cmdcall` (
  `entry_id` int(11) NOT NULL,
  `cmd` int(11) NOT NULL,
  `params` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Дамп данных таблицы `log_cmdcall`
--


-- --------------------------------------------------------

--
-- Структура таблицы `log_cmdcall_schedule_group`
--

DROP TABLE IF EXISTS `log_cmdcall_schedule_group`;
CREATE TABLE `log_cmdcall_schedule_group` (
  `entry_id` int(11) NOT NULL,
  `group` varchar(9) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `date` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Дамп данных таблицы `log_cmdcall_schedule_group`
--


-- --------------------------------------------------------

--
-- Структура таблицы `log_entry`
--

DROP TABLE IF EXISTS `log_entry`;
CREATE TABLE `log_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `action_code` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `log_entry`
--


-- --------------------------------------------------------

--
-- Структура таблицы `log_incoming`
--

DROP TABLE IF EXISTS `log_incoming`;
CREATE TABLE `log_incoming` (
  `entry_id` int(11) NOT NULL,
  `uin` varchar(9) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Дамп данных таблицы `log_incoming`
--

