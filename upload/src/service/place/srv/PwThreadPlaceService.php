<?php
/**
 * @fileName: PwThreadPlaceService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-26 13:39:38
 * @desc: 
 **/
class PwThreadPlaceService {

    public function addPlace($data){
        $tid = isset($data['tid']) ? intval($data['tid']) : 0;
        $from_type = isset($data['from_type']) ? intval($data['from_type']) : 0;
        $created_address = isset($data['created_address']) ? $data['created_address'] : '';
        $area_code = isset($data['area_code']) ? $data['area_code'] : '';

        //
        if( $tid ){
            return $this->_getThreadPlaceDao()->addPlace($data);
        }else{
            return false;
        }
    }
    
    /**
     * 根据帖子的tids批量获取数据
     */
    public function fetchByTids($tids){
        if( !is_array($tids) ){
            return array();
        }else{
            $_data = $this->_getThreadPlaceDao()->fetchByTids($tids);
            $data = array();
            foreach($_data as $v){
                $data[$v['tid']] = $v;
            }
            return $data;
        }
    }

    private function _getThreadPlaceDao(){
        return Wekit::loadDao('place.dao.PwThreadPlaceDao');
    }

}
