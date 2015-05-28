CREATE TABLE IF NOT EXISTS `history_latest` (
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) DEFAULT NULL,
  `value` double(16,4) DEFAULT NULL,
  `str_clock` int(11) DEFAULT NULL,
  `str_value` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `text_clock` int(11) DEFAULT NULL,
  `text_value` text COLLATE utf8_bin,
  `uint_clock` int(11) DEFAULT NULL,
  `uint_value` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TRIGGER IF EXISTS `history_AINS_latest`;
DROP TRIGGER IF EXISTS `history_str_AINS_latest`;
DROP TRIGGER IF EXISTS `history_text_AINS_latest`;
DROP TRIGGER IF EXISTS `history_uint_AINS_latest`;

DELIMITER $$
CREATE TRIGGER `history_AINS_latest` AFTER INSERT ON `history` FOR EACH ROW
INSERT INTO history_latest (itemid, clock, `value`) VALUE (NEW.itemid, NEW.`clock`, NEW.`value`) 
ON DUPLICATE KEY UPDATE `clock` = VALUES(`clock`), `value` = VALUES(`value`);
$$

CREATE TRIGGER `history_str_AINS_latest` AFTER INSERT ON `history_str` FOR EACH ROW
INSERT INTO history_latest (itemid, str_clock, str_value) VALUE (NEW.itemid, NEW.`clock`, NEW.`value`) 
ON DUPLICATE KEY UPDATE `str_citemslock` = VALUES(`str_clock`), `str_value` = VALUES(`str_value`);
$$


CREATE TRIGGER `history_text_AINS_latest` AFTER INSERT ON `history_text` FOR EACH ROW
INSERT INTO history_latest (itemid, text_clock, text_value) VALUE (NEW.itemid, NEW.`clock`, NEW.`value`) 
ON DUPLICATE KEY UPDATE `text_clock` = VALUES(`text_clock`), `text_value` = VALUES(`text_value`);
$$


CREATE TRIGGER `history_uint_AINS_latest` AFTER INSERT ON `history_uint` FOR EACH ROW
INSERT INTO history_latest (itemid, uint_clock, uint_value) VALUE (NEW.itemid, NEW.`clock`, NEW.`value`) 
ON DUPLICATE KEY UPDATE `uint_clock` = VALUES(`uint_clock`), `uint_value` = VALUES(`uint_value`);
$$