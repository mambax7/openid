#
# Table structure for table `openid_localid`
#

CREATE TABLE openid_localid (
  `id` int(10) unsigned NOT NULL auto_increment,
  `openid` varchar(255) default NULL,
  `displayid` varchar(255) default NULL,
  `localid` varchar(25) default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=ujis AUTO_INCREMENT=1 ;

