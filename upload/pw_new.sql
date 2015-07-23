/* qiwen */

DROP TABLE IF EXISTS `{pre}user_open_account`;

CREATE TABLE `{pre}user_open_account` (
  `uid` int(10) unsigned NOT NULL COMMENT '用户id',
  `account` varchar(40) DEFAULT NULL COMMENT '账号名',
  `type` varchar(10) DEFAULT NULL COMMENT '账号类型',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `idx_account` (`account`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='开放平台帐号对应表';

DROP TABLE IF EXISTS `{pre}banner`;

CREATE TABLE `{pre}banner` (
  `banner_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `banner_type` varchar(20) NOT NULL COMMENT 'banner的类别，可能会有多个位置出现',
  `type` varchar(20) DEFAULT NULL COMMENT '一个类型中的分类',
  `title` varchar(50) DEFAULT NULL COMMENT '说明，标题',
  `href` varchar(100) DEFAULT NULL COMMENT '链接',
  `img` varchar(100) DEFAULT NULL COMMENT '图片',
  `vieworder` int(10) unsigned DEFAULT NULL COMMENT '排序',
  PRIMARY KEY (`banner_id`),
  KEY `idx_banner_type` (`banner_type`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{pre}fresh_site`;

CREATE TABLE `{pre}fresh_site` (
    `fresh_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(50) DEFAULT NULL COMMENT '说明，标题',
    `href` varchar(100) DEFAULT NULL COMMENT '链接',
    `img` varchar(100) DEFAULT NULL COMMENT '图片',
    `des` varchar(100) DEFAULT NULL COMMENT '说明',
    `vieworder` int(10) unsigned DEFAULT NULL COMMENT '排序',
    PRIMARY KEY (`fresh_id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};


DROP TABLE IF EXISTS `{pre}collect_content`;

CREATE TABLE `{pre}collect_content` (
`collect_id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
`created_userid`  int(10) UNSIGNED NULL DEFAULT NULL ,
`fid`  int(10) UNSIGNED NULL DEFAULT NULL ,
`tid`  int(10) UNSIGNED NULL DEFAULT NULL ,
`created_time`  int(10) UNSIGNED NULL DEFAULT NULL ,
PRIMARY KEY (`collect_id`),
UNIQUE INDEX `idx_createduserid_tid` (`created_userid`, `tid`),
INDEX `idx_fid` (`fid`)
)ENGINE=MyISAM DEFAULT CHARACTER SET={charset} COMMENT='帖子收藏表';


/* zhusi */

DROP TABLE IF EXISTS `{pre}bbs_forum_life`;

CREATE TABLE `{pre}bbs_forum_life` (
  `fid` smallint(5) unsigned NOT NULL,
  `address` varchar(200) NOT NULL DEFAULT '' COMMENT '商家地址',
  `url` varchar(200) NOT NULL DEFAULT '' COMMENT '商家淘宝店',
  PRIMARY KEY (`fid`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};


DROP TABLE IF EXISTS `{pre}bbs_posts_place`;

CREATE TABLE `{pre}bbs_posts_place` (
  `pid` int(10) unsigned NOT NULL,
  `created_address` varchar(255) NOT NULL DEFAULT '',
  `area_code` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};


DROP TABLE IF EXISTS `{pre}bbs_threads_place`;

CREATE TABLE `{pre}bbs_threads_place` (
  `tid` int(10) unsigned NOT NULL,
  `from_type` tinyint(1) NOT NULL DEFAULT '0',
  `created_address` varchar(255) NOT NULL DEFAULT '',
  `area_code` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};


DROP TABLE IF EXISTS `{pre}bbs_threads_weight`;

CREATE TABLE `{pre}bbs_threads_weight` (
  `tid` int(10) unsigned NOT NULL,
  `weight` int(10) NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_userid` int(10) NOT NULL DEFAULT '0',
  `create_username` varchar(15) NOT NULL DEFAULT '',
  `isenable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `weight` (`weight`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

REPLACE INTO {pre}user_permission_groups (`gid`, `rkey`, `rtype`, `rvalue`, `vtype`) VALUES ('3', 'allow_publish_tao', 'basic', '1', 'string');

DELETE FROM `{pre}common_cron` WHERE `cron_file`='PwCronDoUpdateWeight';

INSERT INTO {pre}common_cron SET  `subject` ='热帖权重计算', `loop_type` ='now', `cron_file` ='PwCronDoUpdateWeight', `isopen` ='2', `created_time` ='{time}', `loop_daytime` ='0-2-0', `next_time` ='{time}';
REPLACE INTO {pre}common_config (`name`, `namespace`, `value`, `vtype`, `description`) VALUES ('goodRecommend', 'freshSetting', '1', 'string', NULL);
REPLACE INTO {pre}common_config (`name`, `namespace`, `value`, `vtype`, `description`) VALUES ('hotTopic', 'freshSetting', '1', 'string', NULL);
REPLACE INTO {pre}common_config (`name`, `namespace`, `value`, `vtype`, `description`) VALUES ('lifeService', 'freshSetting', '1', 'string', NULL);
REPLACE INTO {pre}common_config (`name`, `namespace`, `value`, `vtype`, `description`) VALUES ('sameCity', 'freshSetting', '1', 'string', NULL);