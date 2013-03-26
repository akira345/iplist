--
-- データベース: `iplist`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `iplist`
--

CREATE TABLE IF NOT EXISTS `iplist` (
  `wariate` varchar(10) NOT NULL,
  `country` varchar(2) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `kosu` int(20) NOT NULL,
  `wariate_year` int(8) NOT NULL,
  `jyokyo` varchar(10) NOT NULL,
  `netblock` varchar(20) NOT NULL,
  KEY `netblock` (`netblock`),
  KEY `wariate` (`wariate`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `iplist_trn`
--

CREATE TABLE IF NOT EXISTS `iplist_trn` (
  `wariate` varchar(10) NOT NULL,
  `country` varchar(2) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `kosu` int(20) NOT NULL,
  `wariate_year` int(8) NOT NULL,
  `jyokyo` varchar(10) NOT NULL,
  `netblock` varchar(20) NOT NULL,
  KEY `netblock` (`netblock`),
  KEY `wariate` (`wariate`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
