<?php

class TableBuilder
{
    /**
     * 毕业综合实践表
     * @param string $table
     * @return string
     */
    public function byzhsj($table){
        return 'CREATE TABLE IF NOT EXISTS `{'.$table.'}` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT "毕业综合实践活动id",
  `xid` int(11) NOT NULL COMMENT "学生id",
  `xq` int(2) NOT NULL COMMENT "学期",
  `isfill` int(2) NOT NULL COMMENT "填写状态",
  `yanjxxx` text COMMENT "研究型学习",
  `yanjxxx_f` char(10) COMMENT "研究型学习评分",
  `sheqfw` text COMMENT "社区服务",
  `sheqfw_f` char(10) COMMENT "社区服务评分",
  `shehsj` text COMMENT "社会实践",
  `shehsj_f` char(10) COMMENT "社会实践评分",
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT="毕业综合实践活动" AUTO_INCREMENT=0';
    }
}