<?php
Wind::import('SRV:user.PwUser');
Wind::import('SRV:user.srv.PwLoginService');
Wind::import('APPS:u.service.helper.PwUserHelper');
Wind::import('APPS:u.service.PwThirdLoginService');

/**
 * 登录
 *
 * @author xiaoxia.xu <xiaoxia.xuxx@aliyun-inc.com>
 * @author shangyuanchun <yuanchun.syc@alibaba-inc.com>
 * @copyright ©2003-2015 phpwind.com
 * @license http://www.phpwind.com
 * @version $Id: LoginController.php 24383 2013-01-29 10:09:39Z jieyin $
 * @package products.u.controller
 */
class LoginController extends PwBaseController {
	
	/*
	 * (non-PHPdoc) @see PwBaseController::beforeAction()
	 */
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
		$action = $handlerAdapter->getAction();
		if ($this->loginUser->isExists() && !in_array($action, array('showverify', 'logout', 'show'))) {
			
			$inviteCode = $this->getInput('invite');
			if ($inviteCode) {
				$user = Wekit::load('SRV:invite.srv.PwInviteFriendService')->invite($inviteCode, $this->loginUser->uid);
				if ($user instanceof PwError) {
					$this->showError($user->getError());
				}
			}
			
			if ($action == 'fast') {
				$this->showMessage('USER:login.success');
			} elseif ($action == 'welcome') {
				$this->forwardAction('u/login/show');
			} elseif($this->getRequest()->getIsAjaxRequest()) {
				$this->showError('USER:login.exists');
			} else {
				$this->forwardRedirect($this->_filterUrl());
			}
		}
	}
	
	/*
	 * (non-PHPdoc) 页面登录页 @see WindController::run()
	 */
	public function run() {
		$this->setOutput($this->_showVerify(), 'verify');
		$this->setOutput('用户登录', 'title');
		$this->setOutput($this->_filterUrl(false), 'url');
		$this->setOutput(PwUserHelper::getLoginMessage(), 'loginWay');
        $this->setOutput($this->getInput('invite'), 'invite');
        $service = new PwThirdLoginService();
        $this->setOutput($service->getPlatforms(), 'thirdlogin');
		$this->setTemplate('login');
		
		Wind::import('SRV:seo.bo.PwSeoBo');
		$seoBo = PwSeoBo::getInstance();
		$lang = Wind::getComponent('i18n');
		$seoBo->setCustomSeo($lang->getMessage('SEO:u.login.run.title'), '', '');
		Wekit::setV('seo', $seoBo);
    }

    public function thirdloginAction()
    {
        $platform = $this->getInput('platform', 'get');
        if (!isset(PwThirdLoginService::$supportedPlatforms[$platform])) {
            $this->showError('USER:third.platform.error');
        }
        $service = new PwThirdLoginService();
        $this->forwardRedirect($service->getAuthorizeUrl($platform));
    }

    public function thirdlogincallbackAction()
    {
        $platform = $this->getInput('platform', 'get');
        if (!isset(PwThirdLoginService::$supportedPlatforms[$platform])) {
            $this->showError('USER:third.platform.error');
        }
        $service  = new PwThirdLoginService();
        // TODO: 确认第三方返回的授权码
        $authcode = $this->getInput('code', 'get');
        $result   = $service->getAccessToken($platform, $authcode);
        if (!$result[0]) {
            if (is_array($result[1])) {
                $error =  new PwError('USER:third.platform.dataerror.detail',
                                array('{code}' => $result[1][0], '{msg}' => $result[1][1]));
                $this->showError($error->getError());
            } else {
                $this->showError('USER:third.platform.dataerror');
            }
        }
        $accesstoken = $result[1];
        $result = $service->getUserInfo($platform, $accesstoken, $result['extra']);
        if (!$result[0]) {
            if (is_array($result[1])) {
                $error =  new PwError('USER:third.platform.dataerror.detail',
                                array('{code}' => $result[1]['code'], '{msg}' => $result[1]['msg']));
                $this->showError($error->getError());
            } else {
                $this->showError('USER:third.platform.dataerror');
            }
        }
        $userdata = $result[1];
        $acctRelationData = $this->_getUserOpenAccountDs()->getUid($userdata['uid'], $userdata['type']);
//        var_dump($acctRelationData);exit;
        if(empty($acctRelationData)){//如果没有这个用户,跳转到第三方登录注册页，$userdata内容保存到注册页面hidden
//            var_dump($userdata,$_GET,$_POST);
            $this->setOutput($userdata,'userdata');
        }else{//已经注册过，继续登录
            $login = new PwLoginService();
            $this->runHook('c_login_dorun', $login);

            Wind::import('SRV:user.srv.PwRegisterService');
            $registerService = new PwRegisterService();
            $info = $registerService->sysUser($acctRelationData['uid']);
            if (!$info) {
                $this->showError('USER:user.syn.error');
            }
            $identity = PwLoginService::createLoginIdentify($info);
            $identity = base64_encode($identity . '|' . $this->getInput('backurl') . '|' . /* rememberme = */'0');

            $this->forwardRedirect(WindUrlHelper::createUrl('u/login/welcome', array('_statu' => $identity)));
        }
        
    }
    
    /**
     * 执行第三方授权登录后的注册动作
     * 
     */
    public function doThirdRegistAction(){
        //第三方登录注册不校验验证码
        /*
        if(Wekit::load("verify.srv.PwCheckVerifyService")->checkVerify($this->getInput('code')) != true){
            $this->showError('USER:verifycode.error','index.php?m=u&c=login');
        }
         */
        //post参数中获取用户注册信息、第三方授权获取的信息通过hidden标签保存
        $username = $this->getInput('username','post');
        $password = $this->getInput('password','post');
        $email = $this->getInput('email','post');
        $gender = $this->getInput('gender','post');
        $uid = $this->getInput('uid','post');
        $type = $this->getInput('type','post');
        $avatar = $this->getInput('avatar','post');
        //执行注册
        Wind::import('SRC:service.user.dm.PwUserInfoDm');
        $userDm = new PwUserInfoDm();
        $userDm->setUsername($username);
        $userDm->setPassword($password);
        $userDm->setEmail($email);
        $userDm->setGender($gender);
        $userDm->setRegdate(Pw::getTime());
        $userDm->setLastvisit(Pw::getTime());
        $userDm->setRegip(Wind::getComponent('request')->getClientIp());
        $userDm->setStatus(0);

        Wind::import('SRV:user.srv.PwRegisterService');
        $registerService = new PwRegisterService();
        $registerService->setUserDm($userDm);
        // [u_regsiter]:插件扩展
        $this->runHook('c_register', $registerService);
        if (($info = $registerService->register()) instanceof PwError) {
            $this->showError($info->getError());
        }
        // 这里取了lastInsertId，但已经指定了主键的值，所以返回false表示成功。。
        if ($this->_getUserOpenAccountDs()->addUser($info['uid'], $uid, $type) == false) {
            $this->downloadThirdPlatformAvatar($info['uid'], $avatar);
        }
        // 以便后面登录用到
        $acctRelationData = array('uid' => $info['uid']);
        //继续登录
        $login = new PwLoginService();
        $this->runHook('c_login_dorun', $login);

        Wind::import('SRV:user.srv.PwRegisterService');
        $registerService = new PwRegisterService();
        $info = $registerService->sysUser($acctRelationData['uid']);
        if (!$info) {
            $this->showError('USER:user.syn.error');
        }
        $identity = PwLoginService::createLoginIdentify($info);
        $identity = base64_encode($identity . '|' . $this->getInput('backurl') . '|' . /* rememberme = */'0');

        $this->forwardRedirect(WindUrlHelper::createUrl('u/login/welcome', array('_statu' => $identity)));
    }

    protected function downloadThirdPlatformAvatar($uid, $avatar_url)
    {
        Wind::import('WSRV:base.WindidUtility');
        $image_content = WindidUtility::buildRequest($avatar_url,array(),true,2,'get');

        if ($image_content) {
            $temp_file = tempnam(PUBLIC_PATH."data/tmp/",'tmp_');
            $handle = fopen($temp_file, "w");
            if ($handle) {
                $res = fwrite($handle, $image_content);
                fclose($handle);

                Wind::import('WSRV:upload.action.WindidAvatarUpload');
                Wind::import('LIB:upload.PwUpload');
                $bhv = new WindidAvatarUpload($uid);
                $upload = new PwUpload($bhv);

                $value= array('name'=>'avatar.jpg','size'=>1024*1024*1,'tmp_name'=>$temp_file);
                $file = new PwUploadFile('_0', $value);
                $file->filename = $upload->filterFileName($bhv->getSaveName($file));
                $file->savedir = $bhv->getSaveDir($file);
                $file->store = Wind::getComponent($bhv->isLocal ? 'localStorage' : 'storage');
                $file->source = str_replace('attachment',
                                            'windid/attachment',
                                            $file->store->getAbsolutePath($file->filename, $file->savedir));

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
	 * 快捷登录
	 */
	public function fastAction() {
		$this->setOutput($this->_showVerify(), 'verify');
		$this->setOutput($this->_filterUrl(), 'url');
		$this->setOutput(PwUserHelper::getLoginMessage(), 'loginWay');
		$this->setTemplate('login_fast');
	}

	/**
	 * 页面登录
	 */
    public function dorunAction() {
		$userForm = $this->_getLoginForm();
		
		/* [验证验证码是否正确] */
		if ($this->_showVerify()) {
            $veryfy = $this->_getVerifyService();
            if ($veryfy->checkVerify($userForm['code']) !== true) {
				$this->showError('USER:verifycode.error');
			}
		}
		$question = $userForm['question'];
		if ($question == -4) {
			$question = $this->getInput('myquestion', 'post');
		}
		
		/* [验证用户名和密码是否正确] */
		$login = new PwLoginService();
		$this->runHook('c_login_dorun', $login);
		
		$isSuccess = $login->login($userForm['username'], $userForm['password'], $this->getRequest()->getClientIp(), $question, $userForm['answer']);
		if ($isSuccess instanceof PwError) {
			$this->showError($isSuccess->getError());
		}
		$config = Wekit::C('site');
		if ($config['windid'] != 'local') {
			$localUser = $this->_getUserDs()->getUserByUid($isSuccess['uid'], PwUser::FETCH_MAIN); 
			if ($localUser['username'] && $userForm['username'] != $localUser['username']) $this->showError('USER:user.syn.error');
		}

		Wind::import('SRV:user.srv.PwRegisterService');
		$registerService = new PwRegisterService();
		$info = $registerService->sysUser($isSuccess['uid']);

		if (!$info)  $this->showError('USER:user.syn.error');
		$identity = PwLoginService::createLoginIdentify($info);
		$identity = base64_encode($identity . '|' . $this->getInput('backurl') . '|' . $userForm['rememberme']);
		
		/* [是否需要设置安全问题] */
		/* @var $userService PwUserService */
		$userService = Wekit::load('user.srv.PwUserService');
		//解决浏览器记录用户帐号和密码问题
		if ($isSuccess['safecv'] && !$question) {
			$this->addMessage(true, 'qaE');
			$this->showError('USER:verify.question.empty');
        }

        //该帐号必须设置安全问题
		if (empty($isSuccess['safecv']) && $userService->mustSettingSafeQuestion($info['uid'])) {
			$this->addMessage(array('url' => WindUrlHelper::createUrl('u/login/setquestion', array('v' => 1, '_statu' => $identity))), 'check');
		}
		$this->showMessage('', 'u/login/welcome?_statu=' . $identity);
	}

	/**
	 * 页头登录
	 */
    public function dologinAction() {
        
        //快捷登录功能关闭
        return;

        //
        $userForm = $this->_getLoginForm();

        $login = new PwLoginService();
        $result = Wekit::load('user.PwUser')->getUserByName($userForm['username']);

        //如果开启了验证码 
        Wind::import('SRV:user.srv.PwRegisterService');
        $registerService = new PwRegisterService();
        $info = $registerService->sysUser($result['uid']);
        $identity = PwLoginService::createLoginIdentify($info);
        $backUrl = $this->getInput('backurl');
        if (!$backUrl) $backUrl = $this->getRequest()->getServer('HTTP_REFERER');
        $identity = base64_encode($identity . '|' . $backUrl . '|' . $userForm['rememberme']);

        $url = '';
        if ($result['safecv']) {
            $url = WindUrlHelper::createUrl('u/login/showquestion', array('_statu' => $identity));
        } elseif (Wekit::load('user.srv.PwUserService')->mustSettingSafeQuestion($info['uid'])) {
            $url = WindUrlHelper::createUrl('u/login/setquestion', array('_statu' => $identity));
        } elseif ($this->_showVerify()) {
            $url = WindUrlHelper::createUrl('u/login/showquestion', array('_statu' => $identity));
        }
        if( $url!='' ){
            $url =  WindUrlHelper::createUrl('u/login/run', array('_statu' => $identity) );
            $this->addMessage(array('url' => ''), 'check');
            $this->showMessage('USER:login.success', 'u/login/run/?_statu=' . $identity);
            return;
        }

        //----
		$userForm = $this->_getLoginForm();
		
		$login = new PwLoginService();
		$result = $login->login($userForm['username'], $userForm['password'], $this->getRequest()->getClientIp());
        if ($result instanceof PwError) {
            $this->showError($result->getError());
        } else {
			$config = Wekit::C('site');
			if ($config['windid'] != 'local') {
				$localUser = $this->_getUserDs()->getUserByUid($result['uid'], PwUser::FETCH_MAIN); 
				if ($localUser['username'] && $userForm['username'] != $localUser['username']) $this->showError('USER:user.syn.error');
            }
			Wind::import('SRV:user.srv.PwRegisterService');
			$registerService = new PwRegisterService();
			$info = $registerService->sysUser($result['uid']);
			$identity = PwLoginService::createLoginIdentify($info);
			$backUrl = $this->getInput('backurl');
			if (!$backUrl) $backUrl = $this->getRequest()->getServer('HTTP_REFERER');
			$identity = base64_encode($identity . '|' . $backUrl . '|' . $userForm['rememberme']);
			
			if ($result['safecv']) {
				$url = WindUrlHelper::createUrl('u/login/showquestion', array('_statu' => $identity));
			} elseif (Wekit::load('user.srv.PwUserService')->mustSettingSafeQuestion($info['uid'])) {
				$url = WindUrlHelper::createUrl('u/login/setquestion', array('_statu' => $identity));
			} elseif ($this->_showVerify()) {
				$url = WindUrlHelper::createUrl('u/login/showquestion', array('_statu' => $identity));
            }
            $this->addMessage(array('url' => $url), 'check');
			$this->showMessage('USER:login.success', 'u/login/welcome?_statu=' . $identity);
		}
	}

	/**
	 * 显示安全问题
	 */
	public function showquestionAction() {
		$statu = $this->checkUserInfo();
		$verify = $this->_showVerify();
		$v = $this->getInput('v', 'get');
		/* @var $userSrv PwUserService */
		$userSrv = Wekit::load('SRV:user.srv.PwUserService');
		$hasQuestion = $userSrv->isSetSafecv($this->loginUser->uid);
		if (!$hasQuestion && (1 == $v || !$verify)) {
			$this->forwardRedirect(WindUrlHelper::createUrl('u/login/welcome', array('_statu' => $statu)));
		}
		if (1 != $v) {
			$this->setOutput($verify, 'verify');
		}
		$this->setOutput($hasQuestion, 'hasQuestion');
		$this->setOutput($this->_getQuestions(), 'safeCheckList');
		$this->setOutput($statu, '_statu');
		$this->setOutput($v, 'v');
		$this->setOutput($this->getInput('s', 'get'), 's');
		$this->setTemplate('login_question');
	}

	/**
	 * 检查安全问题是否正确---也头登录的弹窗，带有验证码
	 */
	public function doshowquestionAction() {
		$statu = $this->checkUserInfo();
		$code = $this->getInput('code', 'post');
		if ($this->_showVerify() && (1 != $this->getInput('v', 'post'))) {
			$veryfy = $this->_getVerifyService();
			if (false === $veryfy->checkVerify($code)) $this->showError('USER:verifycode.error');
		}
		/* @var $userSrv PwUserService */
		$userSrv = Wekit::load('SRV:user.srv.PwUserService');
		$hasQuestion = $userSrv->isSetSafecv($this->loginUser->uid);
		if ($hasQuestion) {
			list($question, $answer) = $this->getInput(array('question', 'answer'), 'post');
			if ($question == -4) {
				$question = $this->getInput('myquestion', 'post');
			}
			Wind::import('SRV:user.srv.PwTryPwdBp');
			$pwdBp = new PwTryPwdBp();
			$result = $pwdBp->checkQuestion($this->loginUser->uid, $question, $answer, $this->getRequest()->getClientIp());
			if ($result instanceof PwError) {
				$this->showError($result->getError());
			}
		}
		$this->showMessage('USER:login.success', 'u/login/welcome?_statu=' . $statu);
	}

	/**
	 * 验证密码
	 */
	public function checkpwdAction() {
		list($password, $username) = $this->getInput(array('password', 'username'), 'post');
		Wind::import('SRV:user.srv.PwTryPwdBp');
		$pwdBp = new PwTryPwdBp();
		$info = $pwdBp->author($username, $password, $this->getRequest()->getClientIp());
		if ($info instanceof PwError) {
			$this->showError($info->getError());
		}
		$this->showMessage();
	}

	/**
	 * 验证安全问题
	 */
	public function checkquestionAction() {
		$statu = $this->checkUserInfo();
		list($question, $answer) = $this->getInput(array('question', 'answer'), 'post');
		Wind::import('SRV:user.srv.PwTryPwdBp');
		$pwdBp = new PwTryPwdBp();
		$result = $pwdBp->checkQuestion($this->loginUser->uid, $question, $answer, $this->getRequest()->getClientIp());
		if ($result instanceof PwError) {
			$this->showError($result->getError());
		}
		$this->showMessage();
	}

	/**
	 * 设置安全问题弹窗
	 */
	public function setquestionAction() {
		$statu = $this->checkUserInfo();
		$mustSetting = Wekit::load('user.srv.PwUserService')->mustSettingSafeQuestion($this->loginUser->uid);
		$verify = $this->_showVerify();
		$v = $this->getInput('v', 'get');
		if (!$mustSetting && (1 == $v || !$verify)) {
			$this->forwardRedirect(WindUrlHelper::createUrl('u/login/welcome', array('_statu' => $statu)));
		}
		if (1 != $v) {
			$this->setOutput($verify, 'verify');
		}
		$this->setOutput($v, 'v');
		$this->setOutput($this->_getQuestions(), 'safeCheckList');
		$this->setOutput($statu, '_statu');
		$this->setTemplate('login_setquestion');
	}

	/**
	 * 执行设置安全问题
	 */
	public function dosettingAction() {
		$statu = $this->checkUserInfo();
		$code = $this->getInput('code', 'post');
		if ($this->_showVerify() && (1 != $this->getInput('v', 'post'))) {
			$veryfy = $this->_getVerifyService();
			if (false === $veryfy->checkVerify($code)) {
				$this->showError('USER:verifycode.error');
			}
		}
		list($question, $answer) = $this->getInput(array('question', 'answer'), 'post');
		if (!$question || !$answer) $this->showError('USER:login.question.setting');
		if (intval($question) === -4) {
			$question = $this->getInput('myquestion', 'post');
			if (!$question) $this->showError('USER:login.question.setting');
		}
		
		/* @var $userDs PwUser */
		$userDs = Wekit::load('user.PwUser');
		$userDm = new PwUserInfoDm($this->loginUser->uid);
		$userDm->setQuestion($question, $answer);
		if (($result = $userDs->editUser($userDm, PwUser::FETCH_MAIN)) instanceof PwError) {
			$this->showError($result->getError());
		}
		$this->showMessage('USER:login.question.setting.success', 'u/login/welcome?_statu=' . $statu);
	}

	/**
	 * 登录成功
	 */
	public function welcomeAction() {
		$identify = $this->checkUserInfo();
		if (Pw::getstatus($this->loginUser->info['status'], PwUser::STATUS_UNACTIVE)) {
			Wind::import('SRV:user.srv.PwRegisterService');
			$identify = PwRegisterService::createRegistIdentify($this->loginUser->uid, $this->loginUser->info['password']);
			$this->forwardAction('u/register/sendActiveEmail', array('_statu' => $identify, 'from' => 'login'), true);
		}
		list(, $refUrl, $rememberme) = explode('|', base64_decode($identify));
		$login = new PwLoginService();
		$login->setLoginCookie($this->loginUser, $this->getRequest()->getClientIp(), $rememberme);
		if (Pw::getstatus($this->loginUser->info['status'], PwUser::STATUS_UNCHECK)) {
			$this->forwardRedirect(WindUrlHelper::createUrl('u/login/show', array('backurl' => $refUrl)));
		}
		if (!$refUrl) $refUrl = Wekit::url()->base;

		if ($synLogin = $this->_getWindid()->synLogin($this->loginUser->uid)) {
			$this->setOutput($this->loginUser->username, 'username');
			$this->setOutput($refUrl, 'refUrl');
			$this->setOutput($synLogin, 'synLogin');
		} else {
			$this->forwardRedirect($refUrl);
		}
	}
	
	/**
	 * 提示信息
	 */
	public function showAction() {
		if (Pw::getstatus($this->loginUser->info['status'], PwUser::STATUS_UNCHECK)) {
			$this->showError('USER:login.active.check');
		}
		$this->forwardRedirect($this->_filterUrl());
	}

	/**
	 * 检查用户输入的用户名
	 */
	public function checknameAction() {
		$login = new PwLoginService();
		$info = $login->checkInput($this->getInput('username'));
		if (!$info) $this->showError('USER:user.error.-14');
		if (!empty($info['safecv'])) {
			Wind::import('SRV:user.srv.PwRegisterService');
			$registerService = new PwRegisterService();
			$status = PwLoginService::createLoginIdentify($registerService->sysUser($info['uid']));
			$identify = base64_encode($status . '|');
			$this->addMessage($this->_getQuestions(), 'safeCheck');
			$this->addMessage($identify, '_statu');
			$this->showMessage();
		}
		$this->showMessage();
	}

	/**
	 * 退出
	 *
	 * @return void
	 */
	public function logoutAction() {
		$this->setOutput('用户登出', 'title');
		/* @var $userService PwUserService */
		$uid = $this->loginUser->uid;
		$username = $this->loginUser->username;
		$userService = Wekit::load('user.srv.PwUserService');
		if (!$userService->logout()) $this->showMessage('USER:loginout.fail');
		$url = $this->getInput('backurl');
		if (!$url) $url = $this->getRequest()->getServer('HTTP_REFERER');
		if (!$url) $url = WindUrlHelper::createUrl('u/login/run');
	
		if ($synLogout = $this->_getWindid()->synLogout($uid)) {
			$this->setOutput($username, 'username');
			$this->setOutput($url, 'refUrl');
			$this->setOutput($synLogout, 'synLogout');
		} else {
			$this->forwardRedirect($url);
		}
	}

	/**
	 * 检查用户信息合法性
	 *
	 * @return string
	 */
	private function checkUserInfo() {
		$identify = $this->getInput('_statu', 'get');
		!$identify && $identify = $this->getInput('_statu', 'post');

		if (!$identify) $this->showError('USER:illegal.request');
		list($identify, $url, $rememberme) = explode('|', base64_decode($identify) . '|');
		list($uid, $password) = PwLoginService::parseLoginIdentify(rawurldecode($identify));
		
// 		$info = $this->_getUserDs()->getUserByUid($uid, PwUser::FETCH_MAIN);
		$this->loginUser = new PwUserBo($uid);
		if (!$this->loginUser->isExists() || Pw::getPwdCode($this->loginUser->info['password']) != $password) {
			$this->showError('USER:illegal.request');
		}
		return base64_encode($identify . '|' . $url . '|' . $rememberme);
	}

	/**
	 * 获得安全问题列表
	 *
	 * @return array
	 */
	private function _getQuestions() {
		$questions = PwUserHelper::getSafeQuestion();
		$questions[-4] = '自定义安全问题';
		return $questions;
	}
	
	/**
	 * 判断是否需要展示验证码
	 * 
	 * @return boolean
	 */
	private function _showVerify() {
		$config = Wekit::C('verify', 'showverify');
		!$config && $config = array();
        if(in_array('userlogin', $config)==true){
            return true;
        }

        //ip限制,防止撞库; 错误三次,自动显示验证码
        $ipDs = Wekit::load('user.PwUserLoginIpRecode');
        $info = $ipDs->getRecode($this->getRequest()->getClientIp());
        return is_array($info) && $info['error_count']>3 ? true : false;
	}
	
	private function _getWindid() {
		return WindidApi::api('user');
	}

	/**
	 * 获得用户DS
	 *
	 * @return PwUser
	 */
	private function _getUserDs() {
		return Wekit::load('user.PwUser');
	}
	
	/**
	 * Enter description here ...
	 *
	 * @return PwCheckVerifyService
	 */
	private function _getVerifyService() {
		return Wekit::load("verify.srv.PwCheckVerifyService");
	}

	/**
	 * 过滤来源URL
	 *
	 * TODO
	 * 
	 * @return string
	 */
	private function _filterUrl($returnDefault = true) {
		$url = $this->getInput('backurl');
		if (!$url) $url = $this->getRequest()->getServer('HTTP_REFERER');
		if ($url) {
			// 排除来自注册页面/自身welcome/show的跳转
			$args = WindUrlHelper::urlToArgs($url);
			if ($args['m'] == 'u' && in_array($args['c'], array('register', 'login'))) {
				$url = '';
			}
		}
		if (!$url && $returnDefault) $url = Wekit::url()->base;
		return $url;
	}

	/**
	 * @return array
	 */
	private function _getLoginForm() {
		$data = array();
		list($data['username'], $data['password'], $data['question'], $data['answer'], $data['code'], $data['rememberme']) = $this->getInput(
			array('username', 'password', 'question', 'answer', 'code', 'rememberme'), 'post');
		if (empty($data['username']) || empty($data['password'])) $this->showError('USER:login.user.require', 'u/login/run');
		return $data;
    }

    /**
     * 开放平台帐号关联ds
     *
     * @access private
     * @return void
     */
    private function _getUserOpenAccountDs()
    {
        return Wekit::load('native.PwOpenAccount');
    }
}
