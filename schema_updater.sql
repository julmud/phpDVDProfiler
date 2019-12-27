# DB schema upgrade for phpdvdprofiler
# replace DVDPROFILER_ with your $tableprefix
# --------------------------------------------------------
#
# Schema update from version 2.7 to 2.8
#
SET @tablename = "DVDPROFILER_dvd";
SET @columnname = "featureplayall";
SET @preparedStatement = (SELECT IF(
  (
    SELECT value FROM DVDPROFILER_dvd_properties WHERE property = 'db_schema_version'
  ) > '2.7',
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " TINYINT UNSIGNED DEFAULT NULL;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "featuredbox";
SET @preparedStatement = (SELECT IF(
  (
    SELECT value FROM DVDPROFILER_dvd_properties WHERE property = 'db_schema_version'
  ) > '2.7',
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " TINYINT UNSIGNED DEFAULT NULL;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "featurecinechat";
SET @preparedStatement = (SELECT IF(
  (
    SELECT value FROM DVDPROFILER_dvd_properties WHERE property = 'db_schema_version'
  ) > '2.7',
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " TINYINT UNSIGNED DEFAULT NULL;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "featuremovieiq";
SET @preparedStatement = (SELECT IF(
  (
    SELECT value FROM DVDPROFILER_dvd_properties WHERE property = 'db_schema_version'
  ) > '2.7',
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " TINYINT UNSIGNED DEFAULT NULL;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT value FROM DVDPROFILER_dvd_properties WHERE property = 'db_schema_version'
  ) = '2.7',
  "UPDATE DVDPROFILER_dvd_properties SET value = '2.8' WHERE property = 'db_schema_version';",
  "SELECT 1"
));
PREPARE updateSchemaVersion FROM @preparedStatement;
EXECUTE updateSchemaVersion;
DEALLOCATE PREPARE updateSchemaVersion;