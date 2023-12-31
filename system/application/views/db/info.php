CREATE TABLE IF NOT EXISTS `%s` (
  `gps_info_id` int(11) NOT NULL AUTO_INCREMENT,
  `gps_info_device` varchar(100) DEFAULT NULL,
  `gps_info_hdop` varchar(4) DEFAULT NULL,
  `gps_info_io_port` varchar(10) DEFAULT NULL,
  `gps_info_distance` float DEFAULT NULL,
  `gps_info_alarm_data` varchar(10) DEFAULT NULL,
  `gps_info_ad_input` varchar(10) DEFAULT NULL,
  `gps_info_utc_coord` int(11) DEFAULT NULL,
  `gps_info_utc_date` int(11) DEFAULT NULL,
  `gps_info_alarm_alert` varchar(4) DEFAULT NULL,
  `gps_info_time` timestamp NULL DEFAULT NULL,
  `gps_info_status` int(11) DEFAULT '0' COMMENT '0=new;1=alarm proccessed',
  `gps_info_gps` int(11) DEFAULT NULL,
  PRIMARY KEY (`gps_info_id`),
  KEY `NewIndex1` (`gps_info_device`),
  KEY `gps_info_time` (`gps_info_time`),
  KEY `NewIndex3` (`gps_info_alarm_alert`,`gps_info_status`),
  KEY `gps_info_workhour_index` (`gps_info_time`,`gps_info_device`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1
