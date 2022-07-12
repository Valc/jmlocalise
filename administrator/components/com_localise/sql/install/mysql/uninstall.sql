DROP TABLE IF EXISTS `#__localise`;
DROP TABLE IF EXISTS `#__localise_revised_values`;
DROP TABLE IF EXISTS `#__localise_false_positives`;
DROP TABLE IF EXISTS `#__localise_known_core_files`;
DROP TABLE IF EXISTS `#__localise_known_deleted_keys`;
DROP TABLE IF EXISTS `#__localise_known_renamed_keys`;
DELETE FROM `#__assets` WHERE `name` LIKE 'com_localise%';
