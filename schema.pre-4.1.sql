#	$Id$
# DB installation schema for phpdvdprofiler
# replace DVDPROFILER_ with your $tableprefix
# --------------------------------------------------------
#
# Table Structure for Table `dvd`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd;
CREATE TABLE DVDPROFILER_dvd (
  id char(20) default NULL,
  upc varchar(30) default NULL,
  builtinmediatype tinyint unsigned default NULL,
  custommediatype varchar(40) default NULL,
  mediabannerfront tinyint signed default NULL,
  mediabannerback tinyint signed default NULL,
  title varchar(250) default NULL,
  sorttitle varchar(250) default NULL,
  originaltitle varchar(250) default NULL,
  description varchar(255) default NULL,
  countryoforigin varchar(255) default NULL,
  countryoforigin2 varchar(255) default NULL,
  countryoforigin3 varchar(255) default NULL,
  region varchar(10) default NULL,
  collectiontype varchar(60) default NULL,
  realcollectiontype varchar(60) default NULL,
  auxcolltype varchar(250) default NULL,
  collectionnumber int unsigned default NULL,
  rating varchar(40) default NULL,
  ratingsystem varchar(80) default NULL,
  ratingage varchar(20) default NULL,
  ratingvariant varchar(20) default NULL,
  ratingdetails varchar(255) default NULL,
  productionyear varchar(4) default NULL,
  released int unsigned default NULL,
  runningtime smallint unsigned default NULL,
  casetype varchar(20) default NULL,
  caseslipcover tinyint unsigned default NULL,
  primegenre varchar(80) default NULL,
  primedirector varchar(200) default NULL,
  isadulttitle tinyint unsigned default NULL,
  formataspectratio varchar(10) default NULL,
  formatcolorcolor tinyint unsigned default NULL,
  formatcolorbw tinyint unsigned default NULL,
  formatcolorcolorized tinyint unsigned default NULL,
  formatcolormixed tinyint unsigned default NULL,
  formatvideostandard varchar(10) default NULL,
  formatletterbox tinyint unsigned default NULL,
  formatpanandscan tinyint unsigned default NULL,
  formatfullframe tinyint unsigned default NULL,
  format16x9 tinyint unsigned default NULL,
  formatdualsided tinyint unsigned default NULL,
  formatduallayered tinyint unsigned default NULL,
  dim2d tinyint unsigned default NULL,
  dim3danaglyph tinyint unsigned default NULL,
  dim3dbluray tinyint unsigned default NULL,
  drhdr10 tinyint unsigned default NULL,
  drdolbyvision tinyint unsigned default NULL,
  featuresceneaccess tinyint unsigned default NULL,
  featureplayall tinyint unsigned default NULL,
  featuretrailer tinyint unsigned default NULL,
  featurebonustrailers tinyint unsigned default NULL,
  featuremakingof tinyint unsigned default NULL,
  featurecommentary tinyint unsigned default NULL,
  featuredeletedscenes tinyint unsigned default NULL,
  featureinterviews tinyint unsigned default NULL,
  featureouttakes tinyint unsigned default NULL,
  featurestoryboardcomparisons tinyint unsigned default NULL,
  featurephotogallery tinyint unsigned default NULL,
  featureproductionnotes tinyint unsigned default NULL,
  featuredvdromcontent tinyint unsigned default NULL,
  featuregame tinyint unsigned default NULL,
  featuremultiangle tinyint unsigned default NULL,
  featuremusicvideos tinyint unsigned default NULL,
  featurethxcertified tinyint unsigned default NULL,
  featureclosedcaptioned tinyint unsigned default NULL,
  featuredigitalcopy tinyint unsigned default NULL,
  featurepip tinyint unsigned default NULL,
  featurebdlive tinyint unsigned default NULL,
  featuredbox tinyint unsigned default NULL,
  featurecinechat tinyint unsigned default NULL,
  featuremovieiq tinyint unsigned default NULL,
  featureother varchar(255) default NULL,
  reviewfilm smallint unsigned default NULL,
  reviewvideo smallint unsigned default NULL,
  reviewaudio smallint unsigned default NULL,
  reviewextras smallint unsigned default NULL,
  srp varchar(10) default NULL,
  srpcurrencyid varchar(10) default NULL,
  srpcurrencyname varchar(100) default NULL,
  srpdec decimal(10,3) default NULL,
  gift tinyint unsigned default NULL,
  giftuid smallint unsigned default 0,
  purchaseprice varchar(10) default NULL,
  purchasepricecurrencyid varchar(10) default NULL,
  purchasepricecurrencyname varchar(100) default NULL,
  paid decimal(10,3) default NULL,
  purchasedate int unsigned default NULL,
  purchaseplace int default NULL,
  loaninfo varchar(250) default NULL,
  loandue int unsigned default NULL,
  overview mediumtext default NULL,
  eastereggs mediumtext default NULL,
  lastedited int unsigned default NULL,
  countas smallint signed default NULL,
  hashprofile varchar(10) default NULL,
  hashnocolid varchar(10) default NULL,
  hashcast varchar(10) default NULL,
  hashcrew varchar(10) default NULL,
  notes mediumtext default NULL,
  wishpriority tinyint unsigned default NULL,
  boxparent varchar(20) default NULL,
  boxchild tinyint unsigned default NULL,
  PRIMARY KEY (id),
  KEY sorttitle (sorttitle),
  KEY collectiontype (collectiontype)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_common_actor`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_common_actor;
CREATE TABLE DVDPROFILER_dvd_common_actor (
  caid int NOT NULL AUTO_INCREMENT,
  firstname varchar(50) default NULL,
  middlename varchar(50) default NULL,
  lastname varchar(50) default NULL,
  birthyear varchar(10) default NULL,
  fullname varchar(200) default NULL,
  PRIMARY KEY (caid),
  KEY fullname (fullname)
) TYPE=MyISAM;
INSERT INTO DVDPROFILER_dvd_common_actor VALUES (-1, '__DiViDeR__', '__Episode__', '__DiViDeR__', '0', '__DiViDeR__');
INSERT INTO DVDPROFILER_dvd_common_actor VALUES (-2, '__DiViDeR__', '__Group__',   '__DiViDeR__', '0', '__DiViDeR__');
INSERT INTO DVDPROFILER_dvd_common_actor VALUES (-3, '__DiViDeR__', '__EndDiv__',  '__DiViDeR__', '0', '__DiViDeR__');
# --------------------------------------------------------
#
# Table Structure for Table `dvd_actor`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_actor;
CREATE TABLE DVDPROFILER_dvd_actor (
  id char(20) NOT NULL,
  lineno smallint NOT NULL,
  caid int default NULL,
  creditedas varchar(250) default NULL,
  role varchar(250) default NULL,
  voice tinyint unsigned default NULL,
  uncredited tinyint unsigned default NULL,
  PRIMARY KEY (id,lineno),
  KEY caid (caid)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_users`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_users;
CREATE TABLE DVDPROFILER_dvd_users (
  uid smallint unsigned NOT NULL,
  firstname varchar(30) default NULL,
  lastname varchar(30) default NULL,
  phonenumber varchar(30) default NULL,
  emailaddress varchar(30) default NULL,
  PRIMARY KEY (uid)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_events`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_events;
CREATE TABLE DVDPROFILER_dvd_events (
  id char(20) NOT NULL,
  uid smallint unsigned default NULL,
  eventtype varchar(30) default NULL,
  note varchar(250) default NULL,
  timestamp DATETIME default NULL,
  KEY id (id),
  KEY uid (uid)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_discs`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_discs;
CREATE TABLE DVDPROFILER_dvd_discs (
  id char(20) NOT NULL,
  discno smallint default NULL,
  discdescsidea varchar(250) default NULL,
  discdescsideb varchar(250) default NULL,
  discidsidea varchar(16) default NULL,
  discidsideb varchar(16) default NULL,
  labelsidea varchar(50) default NULL,
  labelsideb varchar(50) default NULL,
  duallayeredsidea tinyint unsigned default NULL,
  duallayeredsideb tinyint unsigned default NULL,
  dualsided tinyint unsigned default NULL,
  location varchar(250) default NULL,
  slot varchar(250) default NULL,
  KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_audio`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_audio;
CREATE TABLE DVDPROFILER_dvd_audio (
  id char(20) NOT NULL,
  dborder smallint default NULL,
  audiocontent varchar(250) default NULL,
  audioformat varchar(250) default NULL,
  audiochannels varchar(250) default NULL,
  KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_common_credits`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_common_credits;
CREATE TABLE DVDPROFILER_dvd_common_credits (
  caid int NOT NULL AUTO_INCREMENT,
  firstname varchar(50) default NULL,
  middlename varchar(50) default NULL,
  lastname varchar(50) default NULL,
  birthyear varchar(10) default NULL,
  fullname varchar(200) default NULL,
  PRIMARY KEY (caid),
  KEY fullname (fullname)
) TYPE=MyISAM;
INSERT INTO DVDPROFILER_dvd_common_credits VALUES (-1, '__DiViDeR__', '__Episode__', '__DiViDeR__', '0', '__DiViDeR__');
INSERT INTO DVDPROFILER_dvd_common_credits VALUES (-2, '__DiViDeR__', '__Group__',   '__DiViDeR__', '0', '__DiViDeR__');
INSERT INTO DVDPROFILER_dvd_common_credits VALUES (-3, '__DiViDeR__', '__EndDiv__',  '__DiViDeR__', '0', '__DiViDeR__');
# --------------------------------------------------------
#
# Table Structure for Table `dvd_credits`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_credits;
CREATE TABLE DVDPROFILER_dvd_credits (
  id char(20) NOT NULL,
  lineno smallint NOT NULL,
  caid int default NULL,
  creditedas varchar(250) default NULL,
  credittype varchar(250) default NULL,
  creditsubtype varchar(250) default NULL,
  customrole varchar(250) default NULL,
  PRIMARY KEY (id,lineno),
  KEY caid (caid)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_genres`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_genres;
CREATE TABLE DVDPROFILER_dvd_genres (
  id char(20) NOT NULL,
  dborder smallint default NULL,
  genre varchar(250) default NULL,
  KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_boxset`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_boxset;
CREATE TABLE DVDPROFILER_dvd_boxset (
  id char(20) NOT NULL,
  child varchar(20) default NULL,
  KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_studio`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_studio;
CREATE TABLE DVDPROFILER_dvd_studio (
  id char(20) NOT NULL,
  ismediacompany tinyint unsigned default NULL,
  dborder smallint default NULL,
  studio varchar(250) default NULL,
  KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_subtitle`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_subtitle;
CREATE TABLE DVDPROFILER_dvd_subtitle (
  id char(20) NOT NULL,
  subtitle varchar(80) default NULL,
  KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_tags`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_tags;
CREATE TABLE DVDPROFILER_dvd_tags (
  id char(20) NOT NULL,
  name varchar(250) default NULL,
  fullyqualifiedname varchar(250) default NULL,
  KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_stats`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_stats;
CREATE TABLE DVDPROFILER_dvd_stats (
  stattype varchar(20) NOT NULL,
  namestring1 varchar(250) default NULL,
  namestring2 varchar(250) default NULL,
  id char(20) default NULL,
  counts int unsigned default NULL,
  KEY stattype (stattype)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_locks`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_locks;
CREATE TABLE DVDPROFILER_dvd_locks (
  id char(20) UNIQUE NOT NULL,
  entire BOOL default NULL,
  covers BOOL default NULL,
  title BOOL default NULL,
  mediatype BOOL default NULL,
  overview BOOL default NULL,
  regions BOOL default NULL,
  genres BOOL default NULL,
  srp BOOL default NULL,
  studios BOOL default NULL,
  discinfo BOOL default NULL,
  cast BOOL default NULL,
  crew BOOL default NULL,
  features BOOL default NULL,
  audio BOOL default NULL,
  subtitles BOOL default NULL,
  eastereggs BOOL default NULL,
  runningtime BOOL default NULL,
  releasedate BOOL default NULL,
  productionyear BOOL default NULL,
  casetype BOOL default NULL,
  videoformats BOOL default NULL,
  rating BOOL default NULL,
  PRIMARY KEY (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_supplier`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_supplier;
CREATE TABLE DVDPROFILER_dvd_supplier (
  sid int NOT NULL AUTO_INCREMENT,
  suppliername varchar(250) UNIQUE,
  supplierurl varchar(250) default NULL,
  suppliertype char(1) default NULL,
  PRIMARY KEY (sid)
#  KEY suppliername (suppliername)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_properties`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_properties;
CREATE TABLE DVDPROFILER_dvd_properties (
  property varchar(100) NOT NULL,
  value varchar(250) default NULL,
  PRIMARY KEY (property)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_exclusions`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_exclusions;
CREATE TABLE DVDPROFILER_dvd_exclusions (
  id char(20) UNIQUE NOT NULL,
  moviepick BOOL default NULL,
  mobile BOOL default NULL,
  iphone BOOL default NULL,
  remoteconnections BOOL default NULL,
  dpopublic BOOL default NULL,
  dpoprivate BOOL default NULL,
  PRIMARY KEY (id)
) TYPE=MyISAM;
# --------------------------------------------------------
#
# Table Structure for Table `dvd_links`
#
DROP TABLE IF EXISTS DVDPROFILER_dvd_links;
CREATE TABLE DVDPROFILER_dvd_links (
  id char(20) NOT NULL,
  dborder smallint default NULL,
  linktype tinyint unsigned default NULL,
  url varchar(250) default NULL,
  description varchar(80) default NULL,
  category varchar(80) default NULL,
  KEY id (id)
) TYPE=MyISAM;
#
INSERT INTO DVDPROFILER_dvd_properties VALUES ("db_schema_version", "2.9");
# --------------------------------------------------------
