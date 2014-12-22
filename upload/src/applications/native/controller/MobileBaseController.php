<?php
/**
 * mobile应用基础类 
 *
 * @fileName: MobileBaseController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-17 15:53:58
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

class MobileBaseController extends PwBaseController {

    protected $_securityKey = null;

	public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);

        $_config_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
        $this->_securityKey = $_config_securityKey['value'];

        $_POST['_json'] = 1;
	}

    /**
     * json data 生成 
     *
     * @param mixed $arrayData 
     * @access private
     * @return void
     */
    //protected function _jsonEncode($err, $arrayData, $display=true){
    //    $jsonArray['err'] = '';
    //    $jsonArray['data'] = $arrayData;
    //    $json_string = Pw::jsonEncode($jsonArray);
    //    //
    //    if( $display==true ){
    //        echo $json_string;
    //    }else{
    //        return $json_string;
    //    }
    //}

    /**
     * Enter description here ...
     *
     * @return PwCheckVerifyService
     */
    protected function _getVerifyService() {                                                                                                                
        return Wekit::load("verify.srv.PwCheckVerifyService");
    }

    //public function afterAction($handlerAdapter){
    //    parent::afterAction($handlerAdapter);
    //    exit();
    //}

    /**
     * 
     * 校验登录用户的sessionid是否合法
     *
     * @access protected
     * @return void
     * @example
     <pre>
        post: session
     </pre>
     */
    protected function _checkUserSessionValid(){
        $unsecurityKey = $this->getInput('securityKey');
        $unsecurityKey ='upE8/bmzngysvSECXLkY7s5xsq+e1vyk2WzIhAyewKVgW9AenvFKP+lu7PM=';
        //
        $unsecurityKey = Pw::decrypt($unsecurityKey,$this->_securityKey);
        $securityKey   = Pw::jsonDecode($unsecurityKey);
//        exit;
        //
        if( is_array($securityKey) && isset($securityKey['username']) && isset($securityKey['password']) ){
            $userSrv = Wekit::load('user.srv.PwUserService');
            if (($r = $userSrv->verifyUser($securityKey['username'], $securityKey['password'], 2)) instanceof PwError) {
                $this->showError('USER:login.error.pwd');
            }
            $localUser = Wekit::load('user.PwUser')->getUserByName($securityKey['username'], PwUser::FETCH_MAIN); 
            return $localUser['uid'];
        }else{
            $this->showError("NATIVE:error.sessionkey.error");
        }
    }


}
