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

	public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);

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
        
    }




}
