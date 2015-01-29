<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.srv.post.do.PwPostDoBase');
/**
 * the last known user to change this file in the repository  <$LastChangedBy$>
 * @author $Author$ Foxsee@aliyun.com
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.phpwind.com
 * @version $Id$ 
 * @package 
 */


class PwLikeDoReply extends PwPostDoBase {

	public $likeid;

	public function __construct($likeid) {
		$this->likeid = $likeid;
	}

	public function addPost($pid, $tid) {
		if ($pid < 1 && $tid < 1) return false;
		return  $this->_getLikeContentDs()->updateLastPid($this->likeid, $pid);
	}

    /**
     * 找出所有喜欢回复的用户id 
     * 
     * @param mixed $typeid 
     * @param mixed $formids 
     * @access public
     * @return void
     */
    public function getAllLikeUserids($typeid, $formids){
        $_likeDta = $this->_getLikeContentDs()->getInfoByTypeidFromids($typeid, $formids); 
        $result = array();
        foreach ($_likeDta as $v) {
            $result[$v['fromid']] = explode(',', substr($v['users'],0,strlen($v['users'])-1) );
        }
        return $result;
    }

    private function _getLikeContentDs() {    
        return Wekit::load('like.PwLikeContent');
    }   

}
