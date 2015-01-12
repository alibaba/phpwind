<?php
/**
 * 移动端收藏贴子
 *
 * @fileName: PwCollectService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-09 15:14:01
 * @desc: 
 **/

Wind::import('SRV:collect.srv.PwCollectService');

class PwNativeCollectService extends PwCollectService {

    public function countCollectByUidAndFids($uid, $fids){
        if(empty($uid) || empty($fids))return array();
        return $this->_getCollectDao()->countCollectByUidAndFids($uid, $fids);
    }

    public function getCollectByUidAndFids($uid, $fids, $limit, $offset){
        if(empty($uid) || empty($fids))return array();
        return $this->_getCollectDao()->getCollectByUidAndFids($uid, $fids, $limit, $offset);
    }

    private function _getCollectDao(){
        return Wekit::load('native.dao.PwNativeCollectDao'); 
    }


}
