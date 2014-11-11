
--
-- Table structure for table `wp_wptc_processed_files`
--

CREATE TABLE IF NOT EXISTS `wp_wptc_processed_files` (
  `file` varchar(255) DEFAULT NULL,
  `offset` int(11) NOT NULL DEFAULT '0',
  `uploadid` varchar(50) DEFAULT NULL,
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `backupID` varchar(50) DEFAULT NULL,
  `revision_number` varchar(50) DEFAULT NULL,
  `revision_id` varchar(50) DEFAULT NULL,
  `mtime_during_upload` varchar(22) DEFAULT NULL,
  `uploaded_file_size` text,
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `file` (`file`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;



--
-- Table structure for table `wp_wptc_processed_dbtables`
--

CREATE TABLE IF NOT EXISTS `wp_wptc_processed_dbtables` (
  `name` varchar(255) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--
-- Table structure for table `wp_wptc_options`
--

CREATE TABLE IF NOT EXISTS `wp_wptc_options` (
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `wp_wptc_excluded_files`
--

CREATE TABLE IF NOT EXISTS `wp_wptc_excluded_files` (
  `file` varchar(255) NOT NULL,
  `isdir` tinyint(1) NOT NULL,
  UNIQUE KEY `file` (`file`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `wp_wptc_to_be_restored_files`
--

CREATE TABLE IF NOT EXISTS `wp_wptc_to_be_restored_files` (
  `file` text,
  `revision_id` int(50) DEFAULT NULL,
  `file_id` int(255) NOT NULL AUTO_INCREMENT,
  `offset` int(255) DEFAULT NULL,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `wp_wptc_processed_restored_files`
--

CREATE TABLE IF NOT EXISTS `wp_wptc_processed_restored_files` (
  `file` varchar(255) NOT NULL,
  `offset` int(50) DEFAULT '0',
  `uploadid` varchar(50) DEFAULT NULL,
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `backupID` double DEFAULT NULL,
  `revision_number` varchar(50) DEFAULT NULL,
  `revision_id` varchar(50) DEFAULT NULL,
  `mtime_during_upload` varchar(22) DEFAULT NULL,
  `download_status` text,
  `uploaded_file_size` text,
  `process_type` text,
  `copy_status` text,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


--
-- Table structure for table `wp_wptc_backup_names`
--

CREATE TABLE IF NOT EXISTS `wp_wptc_backup_names` (
  `this_id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_name` text,
  `backup_id` text,
  PRIMARY KEY (`this_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;