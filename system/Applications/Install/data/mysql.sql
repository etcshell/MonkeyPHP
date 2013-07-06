
CREATE TABLE IF NOT EXISTS `jdxpj` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '阶段性评价id',
  `xid` int(11) NOT NULL COMMENT '学生id',
  `xq` int(2) NOT NULL COMMENT '学期',
  `isfill` int(2) NOT NULL COMMENT "填写状态",
  `xueszp` text COMMENT '学生自评',
  `tongxhp` text COMMENT '同学互评',
  `jiaosjy` text COMMENT '教师寄语',
  `jiazjy` text COMMENT '家长寄语',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='阶段性评价' AUTO_INCREMENT=0;

