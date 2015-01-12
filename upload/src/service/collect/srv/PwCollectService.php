<?php
/**
 * @fileName: PwCollectService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-09 15:14:01
 * @desc: 
 **/
class PwCollectService {

    public function addCollect($data){
        if( empty($data['fid']) || empty($data['tid']) ){                                                                                                                    
            return false; 
        }
        return $this->_getCollectDao()->addCollect($data);
    }

    public function delCollect($uid, $tid){
        if( !$uid || !$tid )return false;
        return $this->_getCollectDao()->deleteCollectByUidAndTid($uid, $tid);
    }

    public function getCollect($uid){

    }

    private function _getCollectDao(){
        return Wekit::load('collect.dao.PwCollectDao'); 
    }

}
