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

abstract class MobileBaseController extends PwBaseController {

    protected $_securityKey = null;

	public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $_config_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
        $this->_securityKey = $_config_securityKey['value'];
	}

    /**
     * 校验用户是否登录; 返回appid接口数据
     * 
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=checkLoginStatus
     <br>
     post: securityKey <br>
     response: {"referer":"","refresh":false,"state":"success","data":{"thirdPlatformAppid":{"taobao":{"order":"0","appId":"a123456"}},"userinfo":{"username":"qiwen","avatar":"http:\/\/img1.phpwind.net\/attachout\/avatar\/002\/37\/41\/2374101_small.jpg","gender":0}},"html":"","message":["\u6b22\u8fce\u56de\u6765..."],"__error":""}
     </pre>
     */
    public function checkLoginStatusAction(){
        $data['thirdPlatformAppid'] = $this->thirdPlatformAppid();
        if( $uid=$this->authSessionKey() ){
            $data = array_merge($this->_getUserInfo($uid),$data) ;
            //
            $this->setOutput($data, 'data');
            $this->showMessage('USER:login.success');
        }
        $this->setOutput($data, 'data');
        $this->showMessage('USER:login.success');
    } 
 
    /**
     * 获得第三方平台的appid，用来app生成使用
     * 
     * @access public
     * @return void
     */
    protected function thirdPlatformAppid(){
        $config = Wekit::C()->getValues('thirdPlatform');                                                                                                 
        //
        $apidata = array();
        if( count($config) ){
            foreach($config as $k=>$v){
                $_keys = explode('.',$k);
                $data[$_keys[0]][$_keys[1]] = $v;
            }
            foreach($data as $k=>$v){
                if( $v['status']==1 ){
                    $data[$k] = $v['displayOrder'].'-'.$v['appId'];
                }else{
                    unset($data[$k]);
                }
            }
            asort($data);
            foreach($data as $k=>$v){
                list($order,$appId) = explode('-',$v);
                $apidata[] = array(
                    'platformname'=>$k,
                    'order'=>$order,
                    'appId'=>$appId,
                );
            }
        }
        return $apidata;
    }


    /**
     * Enter description here ...
     *
     * @return PwCheckVerifyService
     */
    protected function _getVerifyService() {                                                                                                                
        return Wekit::load("verify.srv.PwCheckVerifyService");
    }

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
    protected function checkUserSessionValid(){
        if( $uid=$this->authSessionKey() ){
            return $uid;
        }else{
            $this->showError("NATIVE:error.sessionkey.error");
        }
    }

    /**
     * 认证sessionKey是否合法 
     * 
     * @access protected
     * @return integer
     */
    protected function authSessionKey(){
        $unsecurityKey = $this->getInput('securityKey');
        if(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'],'multipart/form-data')!==false){
            $unsecurityKey = urldecode($unsecurityKey);
        }
        $uid = 0; 
        $securityKey = unserialize(Pw::decrypt($unsecurityKey,$this->_securityKey));
        if( is_array($securityKey) && isset($securityKey['username']) && isset($securityKey['password']) ){
            $_userInfo = Wekit::load('user.PwUser')->getUserByName($securityKey['username'], PwUser::FETCH_MAIN);
            if( $_userInfo['username']==$securityKey['username'] && $_userInfo['password']==$securityKey['password'] ){
                $uid=$_userInfo['uid'];
            }
        }
        return $uid;
    }

    /**
     * 关联帐号后，下载第三方平台的头像 
     * 
     * @access protected
     * @return void
     */
    protected function downloadThirdPlatformAvatar($uid,$avatar_url){
        Wind::import('WSRV:base.WindidUtility');
        $image_content = WindidUtility::buildRequest($avatar_url,array(),true,2,'get');

        if( $image_content ){
            $temp_file = tempnam(sys_get_temp_dir(),'tmp_');
            $handle = fopen($temp_file, "w");
            if( $handle ){
                fwrite($handle, $image_content);
                fclose($handle);

                //
                Wind::import('WSRV:upload.action.WindidAvatarUpload');
                Wind::import('LIB:upload.PwUpload');
                $bhv = new WindidAvatarUpload($uid);
                $upload = new PwUpload($bhv);

                $value= array('name'=>'avatar.jpg','size'=>1024*1024*1,'tmp_name'=>$temp_file);
                $file = new PwUploadFile('_0', $value);
                $file->filename = $upload->filterFileName($bhv->getSaveName($file));
                $file->savedir = $bhv->getSaveDir($file);
                $file->store = Wind::getComponent($bhv->isLocal ? 'localStorage' : 'storage');
                $file->source = str_replace('attachment','windid/attachment',$file->store->getAbsolutePath($file->filename, $file->savedir) );

                if (PwUpload::moveUploadedFile($value['tmp_name'], $file->source)) {
                    $image = new PwImage($file->source);
                    if ($bhv->allowThumb()) {
                        $thumbInfo = $bhv->getThumbInfo($file->filename, $file->savedir);
                        foreach ($thumbInfo as $key => $value) {
                            $thumburl = $file->store->getAbsolutePath($value[0], $value[1]);
                            $thumburl = str_replace('attachment','windid/attachment',$thumburl);

                            $result = $image->makeThumb($thumburl, $value[2], $value[3], $quality, $value[4], $value[5]);
                            if ($result === true && $image->filename != $thumburl) {
                                $ts = $image->getThumb();
                            }   
                        }   
                    }
                }
                @unlink($temp_file);
            }
        }
    }


    /**
     * 第三方平台用户登录校验; 返回用户信息
     * 
     * @access protected
     * @return array()
     * @example
     * <pre>
     * //post: access_token&platformname&native_name <br>
     * post: access_token&platformname(如果直接传递token就只需要二个参数)
     * </pre>
     */
    protected function authThirdPlatform(){
        $_oauth = Wekit::load("APPS:native.service.PwThirdOpenPlatformService");
        $_oauth->access_token = $this->getInput('access_token');
        $_oauth->third_platform_name = $this->getInput('platformname');
        //$_oauth->native_name = 'http%3A%2F%2Fwww.iiwoo.com';
        //
        $info = array();
        $_method_name = $_oauth->third_platform_name.'AuthInfo';
        if( method_exists($_oauth,$_method_name) ){
            $info = $_oauth->$_method_name();
        }
        if( empty($info) ){
            $this->showError('NATIVE:error.openaccount.noauth'); 
        }
        $info['type'] = $_oauth->third_platform_name;
        return $info; 
    }

}
