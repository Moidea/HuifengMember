CREATE TABLE `typecho_hf_members` (
  `mid` int(10) unsigned NOT NULL auto_increment COMMENT 'hf_members表主键',
  `name` varchar(200) default NULL COMMENT 'hf_members姓名',
  `position` varchar(200) default NULL COMMENT 'hf_members部门',
  `tel` varchar(11) default 0 COMMENT 'hf_members联系电话',
  `image` varchar(200) default NULL COMMENT 'hf_members头像地址',
  `categories` varchar(200) default NULL COMMENT 'hf_members分类',
  `is_onduty` varchar(10) default '否' COMMENT 'hf_members是否值班',
  `field` varchar(200) default NULL COMMENT '自定义',
  `order` int(10) unsigned default '0' COMMENT 'hf_members排序',
  PRIMARY KEY  (`mid`)
) ENGINE = InnoDB CHARACTER SET %charset% COLLATE %charset%_general_ci;
