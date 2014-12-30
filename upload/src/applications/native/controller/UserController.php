<?php
/**
 * 用户登录,注册等接口
 *
 * 注意：客户端在请求时需要携带cookie <br>
 * csrf_token=pw;PHPSESSID=guid <br>
 * authOpenAccountAction() <br>
 * openAccountRegisterAction() <br>
 * openAccountLoginAction() <br>
 *
 * @fileName: UserController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:10:43
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:user.srv.PwRegisterService');
Wind::import('SRV:user.srv.PwLoginService');
Wind::import('APPS:native.controller.MobileBaseController');

class UserController extends MobileBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}


    /**
     * 登录;并校验验证码
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=user&a=doLogin <br>
     post: username&password&csrf_token&code&_json=1 <br>
     response: 
    {
        "referer": "",
            "refresh": false,
            "state": "fail",
            "message": [
                "帐号不存在"

            ],
        "__error": ""
    }
     </pre>
     */
    public function doLoginAction(){
        
        list($username, $password, $code) = $this->getInput(array('username', 'password', 'code'));
        
        if (empty($username) || empty($password)) $this->showError('USER:login.user.require');

        //
        if( $this->_showVerify() ){
            $veryfy = $this->_getVerifyService();                                                                                                         
            if ($veryfy->checkVerify($code) !== true) {
                $this->showError('USER:verifycode.error');
            }        
        }

        /* [验证用户名和密码是否正确] */
        $login = new PwLoginService();
        $this->runHook('c_login_dorun', $login);

        $isSuccess = $login->login($username, $password, $this->getRequest()->getClientIp());
        if ($isSuccess instanceof PwError) {
            $this->showError($isSuccess->getError());
        }

        //
        Wind::import('SRV:user.srv.PwRegisterService');
        $registerService = new PwRegisterService();
        $info = $registerService->sysUser($isSuccess['uid']);
        if (!$info)  $this->showError('USER:user.syn.error');

        //
        $this->setOutput( $this->_getUserInfo($isSuccess['uid']), 'data');
        $this->showMessage('USER:login.success');
    }

    /**
     * 注册帐号 
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doRegister    <br>
     post: username&password&repassword&email&code 
     response: {err:"",data:""} 
     </pre>
     */
    public function doRegisterAction(){
        list($username,$password,$email,$code) = $this->getInput(array('username','password','email','code'));

        //  验证输入
        Wind::import('Wind:utility.WindValidator');
        $config = $this->_getRegistConfig();
        if (!$username) $this->showError('USER:user.error.-1');
        if (!$password) $this->showError('USER:pwd.require');
        if (!$email) $this->showError('USER:user.error.-6');
        if (!WindValidator::isEmail($email)) $this->showError('USER:user.error.-7');
	
		foreach ($config['active.field'] as $field) {
			if (!$this->getInput($field, 'post')) $this->showError('USER:register.error.require.needField.' . $field);
		}
		if ($config['active.check'] && !$regreason) {
			$this->showError('USER:register.error.require.regreason');
		}

        if( $this->_showVerify() ){
            $veryfy = $this->_getVerifyService();                                                                                                         
            if ($veryfy->checkVerify($code) !== true) {
                $this->showError('USER:verifycode.error');
            }        
        }

        Wind::import('SRC:service.user.dm.PwUserInfoDm');
        $userDm = new PwUserInfoDm();
        $userDm->setUsername($username);
        $userDm->setPassword($password);
        $userDm->setEmail($email);
        $userDm->setRegdate(Pw::getTime());
        $userDm->setLastvisit(Pw::getTime());
        $userDm->setRegip(Wind::getComponent('request')->getClientIp());

        $userDm->setAliww($aliww);
        $userDm->setQq($qq);
        $userDm->setMsn($msn);
        $userDm->setMobile($mobile);
        $userDm->setMobileCode($mobileCode);
        $userDm->setQuestion($question, $answer);
        $userDm->setRegreason($regreason);

        $areaids = array($hometown, $location);
        if ($areaids) {
            $srv = WindidApi::api('area');
            $areas = $srv->fetchAreaInfo($areaids);
            $userDm->setHometown($hometown, isset($areas[$hometown]) ? $areas[$hometown] : '');
            $userDm->setLocation($location, isset($areas[$location]) ? $areas[$location] : '');
        }

        //
		$registerService = new PwRegisterService();
		$registerService->setUserDm( $userDm );
		/*[u_regsiter]:插件扩展*/
		$this->runHook('c_register', $registerService);
		if (($info = $registerService->register()) instanceof PwError) {
			$this->showError($info->getError());
        } else {
            if (1 == Wekit::C('register', 'active.mail')) {
                $this->showMessage('USER:active.sendemail.success');
			} else {
                $this->setOutput($this->_getUserInfo($info['uid']), 'data');                                                                                       
                $this->showMessage('USER:register.success');
			}
		}
    }

    /**
     * 开放帐号登录; (通过第三方开放平台认证通过后,获得的帐号id在本地查找是否存在,如果存在登录成功 ) 
     * 
     * @access public
     * @return string sessionid
     * @example
     <pre>
     post: access_token&platformname(qq|weibo|weixin|taobao)&native_name(回调地址)
     </pre>
     */
    public function openAccountLoginAction(){
        if( $accountData=$this->authThirdPlatform() ){
            //
            $accountRelationData = $this->_getUserOpenAccountDs()->getUid($accountData['uid'],$accountData['type']);
            //还没有绑定帐号
            if( empty($accountRelationData) ){
                $userdata = array(
                    //'securityKey'=>null,
                    'userinfo'=>$accountData,
                );
            }else{
                /* [验证用户名和密码是否正确] */
                $login = new PwLoginService();
                $this->runHook('c_login_dorun', $login);

                Wind::import('SRV:user.srv.PwRegisterService');
                $registerService = new PwRegisterService();
                $info = $registerService->sysUser($accountRelationData['uid']);
                if (!$info) {
                    $this->showError('USER:user.syn.error');
                }
                $userdata = $this->_getUserInfo();
            }
            //success
            $this->setOutput($userdata,'data');
            $this->showMessage('USER:login.success');
        }
    }


    public function tAction(){

        Wind::import('WSRV:base.WindidUtility');
        $_uri = 'http://b.hiphotos.baidu.com/image/pic/item/eaf81a4c510fd9f9ce2a5205262dd42a2834a498.jpg';
        $image = WindidUtility::buildRequest($_uri,array(),true,2,'get');

        //$temp_file = tempnam(sys_get_temp_dir(),'tmp_');
        $temp_file = '/tmp/a.jpg';
        $handle = fopen($temp_file, "w");
        fwrite($handle, $image);
        fclose($handle);

        $value= array('name'=>'1.jpg','size'=>1024*1024*1,'tmp_name'=>$temp_file);

        Wind::import('WSRV:upload.action.WindidAvatarUpload');
        Wind::import('LIB:upload.PwUpload');
        $bhv = new WindidAvatarUpload(3);

        $upload = new PwUpload($bhv);

        $file = new PwUploadFile('_0', $value);
        $file->filename = $upload->filterFileName($bhv->getSaveName($file));
        $file->savedir = $bhv->getSaveDir($file);
        $file->store = Wind::getComponent($bhv->isLocal ? 'localStorage' : 'storage');
        $file->source = str_replace('attachment','windid/attachment',$file->store->getAbsolutePath($file->filename, $file->savedir) );

        if (!PwUpload::moveUploadedFile($value['tmp_name'], $file->source)) {
            $this->showError('upload.fail');
        }   

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

        unlink($temp_file);
exit;
    }



    /**
     * 
     * 开放帐号注册到本系统内
     *
     * @access public
     * @return void
     * @example
     <pre>
     post: access_token&platformname&native_name&username&email&sex
     </pre>
     */
    public function openAccountRegisterAction() {
        if( $accountData=$this->authThirdPlatform() ){
            
            list($username,$email,$sex) = $this->getInput(array('username','email','sex'));
            //随机密码
            $password = substr(str_shuffle('abcdefghigklmnopqrstuvwxyz1234567890~!@#$%^&*()'),0,7);
            //
            Wind::import('SRC:service.user.dm.PwUserInfoDm');
            $userDm = new PwUserInfoDm();
            $userDm->setUsername($username);
            $userDm->setPassword($password);
            $userDm->setEmail($email);
            $userDm->setGender($sex);
            $userDm->setRegdate(Pw::getTime());
            $userDm->setLastvisit(Pw::getTime());
            $userDm->setRegip(Wind::getComponent('request')->getClientIp());

            //
            $registerService = new PwRegisterService();
            $registerService->setUserDm( $userDm );
            /*[u_regsiter]:插件扩展*/
            $this->runHook('c_register', $registerService);
            if (($info = $registerService->register()) instanceof PwError) {
                $this->showError($info->getError());
            } else {
                //这里注册成功，要把第三方帐号的头像下载下来并处理，这里还没有做
                if( $this->_getUserOpenAccountDs()->addUser($info['uid'],$accountData['uid'],$accountData['type'])==false ){
                    $this->showMessage('USER:register.success');
                }
            }
        }
    }

    /**
     * 修改头像 
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doAvatar <br>
     post: securityKey <br>
     postdata: Filename <br>
     curl --form "Filename=@icon1.jpg" '/index.php?m=native&c=user&a=doAvatar'
     </pre>
     */
    public function doAvatarAction(){
        if( $uid=$this->checkUserSessionValid() ){
            Wind::import('WSRV:upload.action.WindidAvatarUpload');
            Wind::import('LIB:upload.PwUpload');
            $bhv = new WindidAvatarUpload($uid);
            //
            $upload = new PwUpload($bhv);
            if (($result = $upload->check()) === true) {
                foreach ($_FILES as $key => $value) {
                    if (!PwUpload::isUploadedFile($value['tmp_name']) || !$bhv->allowType($key)) {
                        continue;
                    }   
                }
                $file = new PwUploadFile($key, $value);
                if (($result = $upload->checkFile($file)) !== true) {
                    $this->showError($result->getError());
                }
                $file->filename = $upload->filterFileName($bhv->getSaveName($file));
                $file->savedir = $bhv->getSaveDir($file);
                $file->store = Wind::getComponent($bhv->isLocal ? 'localStorage' : 'storage');
                $file->source = str_replace('attachment','windid/attachment',$file->store->getAbsolutePath($file->filename, $file->savedir) );
                //
                if (!PwUpload::moveUploadedFile($value['tmp_name'], $file->source)) {
                    $this->showError('upload.fail');
                }

                $image = new PwImage($file->source);
                if ($bhv->allowThumb()) {
                    $thumbInfo = $bhv->getThumbInfo($file->filename, $file->savedir);
                    foreach ($thumbInfo as $key => $value) {
                        $thumburl = $file->store->getAbsolutePath($value[0], $value[1]); 
                        $thumburl = str_replace('attachment','windid/attachment',$thumburl);
                        //
                        $result = $image->makeThumb($thumburl, $value[2], $value[3], $quality, $value[4], $value[5]);
                        if ($result === true && $image->filename != $thumburl) {
                            $ts = $image->getThumb();
                        }
                    } 
                }
                $this->showMessage('success');
            }
            $this->showMessage('operate.fail');
        }
    }

    /**
     * 修改性别 
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doSex <br>
     post: securityKey&gender
     </pre>
     */
    public function doSexAction(){
        if( $uid=$this->checkUserSessionValid() ){
            //$userDm = new PwUserInfoDm($this->loginUser->uid);  
            $userDm = new PwUserInfoDm($uid);
            $userDm->setGender($this->getInput('gender', 'post'));

            /* @var $userDs PwUser */
            $userDs = Wekit::load('user.PwUser');
            $result = $userDs->editUser($userDm, PwUser::FETCH_MAIN + PwUser::FETCH_INFO);

            if ($result instanceof PwError) {
                $this->showError($result->getError());
            }else{
                PwSimpleHook::getInstance('profile_editUser')->runDo($dm);
                $this->showMessage('USER:user.edit.profile.success');
            }
        }
    }

    /**
     * 保存修改密码 
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doPassWord <br>
     post: securityKey&oldPwd&newPwd&rePwd
     </pre>
     */
    public function doPasswordAction(){
        if( $uid=$this->checkUserSessionValid() ){
            list($newPwd, $oldPwd, $rePwd) = $this->getInput(array('newPwd', 'oldPwd', 'rePwd'), 'post');
            if (!$oldPwd) {
                $this->showError('USER:pwd.change.oldpwd.require');
            }   
            if (!$newPwd) {
                $this->showError('USER:pwd.change.newpwd.require');
            }   
            if ($rePwd != $newPwd) {
                $this->showError('USER:user.error.-20');
            }   
            $this->checkOldPwd($uid, $oldPwd);

            Wind::import('SRC:service.user.dm.PwUserInfoDm');
            $userDm = new PwUserInfoDm($uid);
            $userDm->setPassword($newPwd);
            $userDm->setOldPwd($oldPwd);
            /* @var $userDs PwUser */
            $userDs = Wekit::load('user.PwUser');
            if (($result = $userDs->editUser($userDm, PwUser::FETCH_MAIN)) instanceof PwError) {
                $this->showError($result->getError());

            }   
            $this->loginUser->reset();
            $this->showMessage('USER:pwd.change.success');
        }
    }

    /**
     * 是否需要显示验证码 <br>
     * 需要cookie携带 PHPSESSID <br>
     * /index.php?m=verify&a=get&rand=rand()
     * 
     * @access public
     * @return boolean
     * @example
     * <pre>  
     * /index.php?m=native&c=user&a=ifshowVerifycode <br>
     * </pre>
     */
    public function ifShowVerifycodeAction(){
        $this->setOutput($this->_showVerify(), 'data');
        $this->showMessage('success');
    }

    /**
     * 判断是否需要展示验证码
     * 
     * @return boolean
     */
    private function _showVerify() {
        return $result = false;
        //
        $config = Wekit::C('verify', 'showverify');
        !$config && $config = array();
        if(in_array('userlogin', $config)==true){
            $result = true;
        }else{
            //ip限制,防止撞库; 错误三次,自动显示验证码
            $ipDs = Wekit::load('user.PwUserLoginIpRecode');
            $info = $ipDs->getRecode($this->getRequest()->getClientIp());
            $result = is_array($info) && $info['error_count']>3 ? true : false;
        }
        return $result;
    }


    /**
     * 获得用户信息 
     * 
     * @param mixed $uid 
     * @access private
     * @return void
     */
    private function _getUserInfo($uid){
        //
        $_userInfo = Wekit::load('user.PwUser')->getUserByUid($uid, PwUser::FETCH_MAIN+PwUser::FETCH_INFO);

        //登录成功后，加密身份key
        $_idInfo = array(
            'username'=>$_userInfo['username'],
            'password'=>$_userInfo['password'],
        );
        $securityKey = Pw::encrypt( serialize($_idInfo), $this->_securityKey);

        //返回数据
        $_data = array(
            'securityKey'=>$securityKey,
            'userinfo'=>array(
                'uid'=>$_userInfo['uid'],
                'username'=>$_userInfo['username'],
                'avatar'=>Pw::getAvatar($_userInfo['uid'],''),
                'gender'=>$_userInfo['gender'],
            ),
        ); 
        return $_data;
    }


    /**
     * 开放平台帐号关联ds
     * 
     * @access private
     * @return void
     */
    private function _getUserOpenAccountDs() {
        return Wekit::load('native.PwOpenAccount');
    }   


}
