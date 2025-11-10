-- *** STRUCTURE: `tbl_authors` ***
DROP TABLE IF EXISTS `tbl_authors`;
CREATE TABLE `tbl_authors` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen` datetime DEFAULT '1000-01-01 00:00:00',
  `user_type` enum('author','manager','developer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'author',
  `primary` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `default_area` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth_token_active` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `language` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_cache` ***
DROP TABLE IF EXISTS `tbl_cache`;
CREATE TABLE `tbl_cache` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `namespace` VARCHAR(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creation` int(14) NOT NULL DEFAULT '0',
  `expiry` int(14) unsigned DEFAULT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `expiry` (`expiry`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_entries` ***
DROP TABLE IF EXISTS `tbl_entries`;
CREATE TABLE `tbl_entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(11) unsigned NOT NULL,
  `author_id` int(11) unsigned NOT NULL,
  `modification_author_id` int(11) unsigned NOT NULL DEFAULT 1,
  `creation_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `creation_date_gmt` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `modification_date_gmt` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`id`),
  KEY `section_id` (`section_id`),
  KEY `author_id` (`author_id`),
  KEY `creation_date` (`creation_date`),
  KEY `creation_date_gmt` (`creation_date_gmt`),
  KEY `modification_date` (`modification_date`),
  KEY `modification_date_gmt` (`modification_date_gmt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_extensions` ***
DROP TABLE IF EXISTS `tbl_extensions`;
CREATE TABLE `tbl_extensions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` enum('enabled','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enabled',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_extensions_delegates` ***
DROP TABLE IF EXISTS `tbl_extensions_delegates`;
CREATE TABLE `tbl_extensions_delegates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `extension_id` int(11) NOT NULL,
  `page` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delegate` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `callback` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `extension_id` (`extension_id`),
  KEY `page` (`page`),
  KEY `delegate` (`delegate`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields` ***
DROP TABLE IF EXISTS `tbl_fields`;
CREATE TABLE `tbl_fields` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `element_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_section` int(11) NOT NULL DEFAULT '0',
  `required` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `sortorder` int(11) NOT NULL DEFAULT '1',
  `location` enum('main','sidebar') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main',
  `show_column` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`),
  KEY `index` (`element_name`,`type`,`parent_section`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_author` ***
DROP TABLE IF EXISTS `tbl_fields_author`;
CREATE TABLE `tbl_fields_author` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `allow_multiple_selection` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `default_to_current_user` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_types` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_checkbox` ***
DROP TABLE IF EXISTS `tbl_fields_checkbox`;
CREATE TABLE `tbl_fields_checkbox` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `default_state` enum('on','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on',
  `description` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_color` ***
DROP TABLE IF EXISTS `tbl_fields_color`;
CREATE TABLE `tbl_fields_color` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `default` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_date` ***
DROP TABLE IF EXISTS `tbl_fields_date`;
CREATE TABLE `tbl_fields_date` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `pre_populate` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calendar` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `time` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_email` ***
DROP TABLE IF EXISTS `tbl_fields_email`;
CREATE TABLE `tbl_fields_email` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) UNSIGNED NOT NULL,
  `placeholder` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_input` ***
DROP TABLE IF EXISTS `tbl_fields_input`;
CREATE TABLE `tbl_fields_input` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `validator` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placeholder` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_month` ***
DROP TABLE IF EXISTS `tbl_fields_month`;
CREATE TABLE `tbl_fields_month` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `field_id` INT(11) UNSIGNED NOT NULL,
    `default_month` ENUM('on','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on',
    `custom_month` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,  -- Format: YYYY-MM
    `min` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `max` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `step` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,  -- Steps in month (can be 'any'or int)
    PRIMARY KEY (`id`),
    UNIQUE KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_number` ***
DROP TABLE IF EXISTS `tbl_fields_number`;
CREATE TABLE `tbl_fields_number` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `field_id` int(11) unsigned NOT NULL,
    `min` FLOAT DEFAULT NULL,
    `max` FLOAT DEFAULT NULL,
    `step` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `field_id` (`field_id`)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_radio` ***
DROP TABLE IF EXISTS `tbl_fields_radio`;
CREATE TABLE `tbl_fields_radio` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `display_inline` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `sort_options` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `static_options` text COLLATE utf8mb4_unicode_ci,
  `dynamic_options` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_range` ***
DROP TABLE IF EXISTS `tbl_fields_range`;
CREATE TABLE `tbl_fields_range` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `field_id` int(11) unsigned NOT NULL,
    `min` FLOAT DEFAULT NULL,
    `max` FLOAT DEFAULT NULL,
    `step` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- steps for range (can be 'any' or int)
    PRIMARY KEY  (`id`),
    UNIQUE KEY `field_id` (`field_id`)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_select` ***
DROP TABLE IF EXISTS `tbl_fields_select`;
CREATE TABLE `tbl_fields_select` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `allow_multiple_selection` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `sort_options` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `static_options` text COLLATE utf8mb4_unicode_ci,
  `dynamic_options` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_taglist` ***
DROP TABLE IF EXISTS `tbl_fields_taglist`;
CREATE TABLE `tbl_fields_taglist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `validator` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pre_populate_source` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`),
  KEY `pre_populate_source` (`pre_populate_source`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_tel` ***
DROP TABLE IF EXISTS `tbl_fields_tel`;
CREATE TABLE `tbl_fields_tel` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `field_id` int(11) unsigned NOT NULL,
    `minlength` FLOAT DEFAULT NULL,
    `maxlength` FLOAT DEFAULT NULL,
    `placeholder` VARCHAR(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `pattern` VARCHAR(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_textarea` ***
DROP TABLE IF EXISTS `tbl_fields_textarea`;
CREATE TABLE `tbl_fields_textarea` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `formatter` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_time` ***
DROP TABLE IF EXISTS `tbl_fields_time`;
CREATE TABLE `tbl_fields_time` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `field_id` INT(11) UNSIGNED NOT NULL,
    `default_time` ENUM('on','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on',
    `custom_time` VARCHAR(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,  -- Format: HH:mm or HH:mm:ss
    `min` VARCHAR(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `max` VARCHAR(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `step` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,  -- Steps for time (can be 'any' or int)
    PRIMARY KEY (`id`),
    UNIQUE KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_upload` ***
DROP TABLE IF EXISTS `tbl_fields_upload`;
CREATE TABLE `tbl_fields_upload` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) unsigned NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validator` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_url` ***
DROP TABLE IF EXISTS `tbl_fields_url`;
CREATE TABLE `tbl_fields_url` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) UNSIGNED NOT NULL,
  `placeholder` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_fields_week` ***
DROP TABLE IF EXISTS `tbl_fields_week`;
CREATE TABLE `tbl_fields_week` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `field_id` INT(11) UNSIGNED NOT NULL,
    `default_week` ENUM('on','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on',
    `custom_week` VARCHAR(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,  -- Format: YYYY-Www
    `min` VARCHAR(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `max` VARCHAR(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `step` VARCHAR(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,  -- Steps in week (can be `any` or int)
    PRIMARY KEY (`id`),
    UNIQUE KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_forgotpass` ***
DROP TABLE IF EXISTS `tbl_forgotpass`;
CREATE TABLE `tbl_forgotpass` (
  `author_id` int(11) NOT NULL DEFAULT '0',
  `token` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_pages` ***
DROP TABLE IF EXISTS `tbl_pages`;
CREATE TABLE `tbl_pages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `handle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `params` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_sources` text COLLATE utf8mb4_unicode_ci,
  `events` text COLLATE utf8mb4_unicode_ci,
  `sortorder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_pages_types` ***
DROP TABLE IF EXISTS `tbl_pages_types`;
CREATE TABLE `tbl_pages_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(11) unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`,`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_sections` ***
DROP TABLE IF EXISTS `tbl_sections`;
CREATE TABLE `tbl_sections` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `handle` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sortorder` int(11) NOT NULL DEFAULT '0',
  `hidden` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `filter` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `navigation_group` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Content',
  `author_id` int(11) unsigned NOT NULL DEFAULT 1,
  `modification_author_id` int(11) unsigned NOT NULL DEFAULT 1,
  `creation_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `creation_date_gmt` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `modification_date_gmt` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `handle` (`handle`),
  KEY `creation_date` (`creation_date`),
  KEY `creation_date_gmt` (`creation_date_gmt`),
  KEY `modification_date` (`modification_date`),
  KEY `modification_date_gmt` (`modification_date_gmt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_sections_association` ***
DROP TABLE IF EXISTS `tbl_sections_association`;
CREATE TABLE `tbl_sections_association` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_section_id` int(11) unsigned NOT NULL,
  `parent_section_field_id` int(11) unsigned DEFAULT NULL,
  `child_section_id` int(11) unsigned NOT NULL,
  `child_section_field_id` int(11) unsigned NOT NULL,
  `hide_association` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `interface` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_section_id` (`parent_section_id`,`child_section_id`,`child_section_field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** STRUCTURE: `tbl_sessions` ***
DROP TABLE IF EXISTS `tbl_sessions`;
CREATE TABLE `tbl_sessions` (
  `session` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_expires` int(10) unsigned NOT NULL DEFAULT '0',
  `session_data` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`session`),
  KEY `session_expires` (`session_expires`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** DATA:`tbl_pages` ***
INSERT INTO `tbl_pages` (`id`, `parent`, `title`, `handle`, `path`, `params`, `data_sources`, `events`, `sortorder`) VALUES (1, NULL, 'Home', 'home', NULL, NULL, NULL, NULL, 1);
INSERT INTO `tbl_pages` (`id`, `parent`, `title`, `handle`, `path`, `params`, `data_sources`, `events`, `sortorder`) VALUES (2, NULL, '404', '404', NULL, NULL, NULL, NULL, 2);
INSERT INTO `tbl_pages` (`id`, `parent`, `title`, `handle`, `path`, `params`, `data_sources`, `events`, `sortorder`) VALUES (3, NULL, '403', '403', NULL, NULL, NULL, NULL, 3);

-- *** DATA:`tbl_pages_types` ***
INSERT INTO `tbl_pages_types` (`id`, `page_id`, `type`) VALUES (1, 1, 'index');
INSERT INTO `tbl_pages_types` (`id`, `page_id`, `type`) VALUES (2, 2, '404');
INSERT INTO `tbl_pages_types` (`id`, `page_id`, `type`) VALUES (3, 3, '403');
INSERT INTO `tbl_pages_types` (`id`, `page_id`, `type`) VALUES (4, 2, 'hidden');
INSERT INTO `tbl_pages_types` (`id`, `page_id`, `type`) VALUES (5, 3, 'hidden');

--
-- Table structure for table `sym_dashboard_panels`
--
DROP TABLE IF EXISTS `tbl_dashboard_panels`;
CREATE TABLE `tbl_dashboard_panels` (
  `id` int(11) NOT NULL AUTO_INCREMENT primary key,
  `label` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `config` text,
  `placement` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** DATA:`tbl_dashboard_panels` ***
INSERT INTO `tbl_dashboard_panels` (`id`, `label`, `type`, `config`, `placement`, `sort_order`) VALUES (1, 'Sytem overview', 'symphony_overview', 'N;', 'secondary', 1);
INSERT INTO `tbl_dashboard_panels` (`id`, `label`, `type`, `config`, `placement`, `sort_order`) VALUES (2, 'Sym8 news', 'rss_reader', 'a:3:{s:3:"url";s:39:"https://sym8.io/public-api/releases.rss";s:4:"show";s:6:"full-3";s:5:"cache";s:3:"720";}', 'secondary', 2);

