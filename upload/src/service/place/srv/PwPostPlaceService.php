<?php
/**
 * @fileName: PwPostPlaceService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-26 14:21:52
 * @desc: 
 **/
class PwPostPlaceService {

    public function addPlace($data){

        $pid = isset($data['pid']) ? intval($data['pid']) : 0;
        $created_address = isset($data['created_address']) ? $data['created_address'] : '';
        $area_code = isset($data['area_code']) ? $data['area_code'] : '';
        if(!$pid) return 0;

        //
        if( $pid ){
            return $this->_getPostPlaceDao()->insertValue($data);
        }else{
            return false;
        }
    }
    
    /**
     * 根据帖子的pids批量获取数据
     */
    public function fetchByPids($pids){
        if( !is_array($pids) ){
            return array();
        }else{
            $_data = $this->_getPostPlaceDao()->fetchByPids($pids);
            $data = array();
            foreach($_data as $v){
                $data[$v['pid']] = $v;
            }
            return $data;
        }
    }

    private function _getPostPlaceDao(){
        return Wekit::loadDao('place.dao.PwPostPlaceDao');
    }

}
