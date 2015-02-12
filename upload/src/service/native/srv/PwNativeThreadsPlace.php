<?php
/**
 * @fileName: PwNativeThreadsPlace.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-02-09 14:02:03
 * @desc: 
 */


class PwNativeThreadsPlace {

    /**
     * 获得帖子发布来源是移动端、pc端 
     * 
     * @param mixed $tids 
     * @access public
     * @return void
     */
    public function getThreadFormTypeByTids($tids){
        if( !is_array($tids) || count($tids)<1  ){
            return array();
        }   
        $result = array();
        $_data = $this->_getThreadsPlaceDao()->fetchByTids($tids);
        if( $_data  ){
            foreach($_data as $v) {
                $result[$v['tid']][] = $v['from_type'];
            }   
        }   
        return $result;
    } 

    private function _getThreadsPlaceDao(){
        return Wekit::load('native.dao.PwThreadsPlaceDao'); 
    }


}
