<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wekit::loadDao('cron.dao.PwCronDao');

class PwNativeCronDao extends PwCronDao {
        
        /**
         * 根据计划任务文件名称获取信息
         */
        public function getByFilename($filename){
            if(!$filename) return array();
            $sql = $this->_bindTable("SELECT * FROM %s WHERE `cron_file` = '$filename'");
            $smt = $this->getConnection()->query($sql);
            return $smt->fetch();
        }
	
}