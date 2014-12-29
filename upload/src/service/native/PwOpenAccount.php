<?php
/**
 * 开放平台帐号关联
 *
 * @fileName: PwOpenAccountService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-19 11:37:12
 * @desc: 
 **/

class PwOpenAccount {

    public function __construct() {
//		$this->loginConfig = Wekit::C('login');
	}
	
	/**
	 * 用户登录
	 *
	 * @param string $uid 用户登录的帐号
	 * @param string $account 用户登录的密码
	 * @return boolean
	 */
    public function addUser($uid, $account, $type) {
        $data = array(
            'uid'=>$uid,
            'account'=>$account,
            'type'=>$type,
        );
        return $this->_getDao()->addUser($data);
    }

    /**
     * 获得一个帐号的uid 
     * 
     * @param string $account 
     * @param string $type 
     * @access public
     * @return int
     */
    public function getUid($account, $type){
       return $this->_getDao()->getUid($account,$type);
    }
	
	/** 
	 * 获得用户Ds
	 *
	 * @return PwUser
	 */
    private function _getDao() {
        return Wekit::loadDao('native.dao.PwUserOpenAccountDao');
	}

}
