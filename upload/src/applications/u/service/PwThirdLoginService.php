<?php

/**
 * 登录
 *
 * @author shangyuanchun <yuanchun.syc@alibaba-inc.com>
 * @copyright ©2003-2015 phpwind.com
 * @license http://www.phpwind.com
 * @version $Id: PwThirdLoginService.php 24383 2015-06-13 14:10:47Z ideal $
 * @package products.u.service
 */

Wind::import('WSRV:base.WindidUtility');

class PwThirdLoginService
{
    public static $supportedPlatforms = array(
        // See http://wiki.open.qq.com/wiki/website/%E4%BD%BF%E7%94%A8Authorization_Code%E8%8E%B7%E5%8F%96Access_Token
        'qq'    => array(
            'img'       => 'http://oss.aliyuncs.com/phpwind-image/4819ac3d87071089648af06c5fb7f204.png',
            'authorize' => 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=%s&redirect_uri=%s&state=phpwind&scope=get_user_info',
            'accesstoken' => 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=%s&client_secret=%s&code=%s&redirect_uri=%s',
            'openid' => 'https://graph.qq.com/oauth2.0/me?access_token=%s',
            'userinfo' => 'https://graph.qq.com/user/get_user_info?access_token=%s&oauth_consumer_key=%s&openid=%s',
            'text' => '使用QQ帐号登录',
        ),
        // See http://open.weibo.com/wiki/%E9%A6%96%E9%A1%B5
        'weibo' => array(
            'img' => 'http://oss.aliyuncs.com/phpwind-image/be7945e0d9521e74b8130a138b8694df.png',
            'authorize' => 'https://api.weibo.com/oauth2/authorize?client_id=%s&redirect_uri=%s&scope=email&state=phpwind&display=default',
            'accesstoken' => 'https://api.weibo.com/oauth2/access_token',
            'userinfo'    => 'https://api.weibo.com/2/users/show.json?access_token=%s&uid=%s',
            'text' => '使用新浪微博帐号登录',
        ),
    );

    public function getPlatforms()
    {
        $platforms = array();

        // 网站的第三方appid和移动app的不一样
        $thirdPlatforms = Wekit::C('webThirdLogin');
        foreach (self::$supportedPlatforms as $p => $c) {
            if (!isset($thirdPlatforms[$p.'.status']) || !$thirdPlatforms[$p.'.status']) {
                continue;
            }
            $c['url']    = 'index.php?m=u&c=login&a=thirdlogin&platform='.$p;
            $platforms[] = $c;
        }

        return $platforms;
    }

    public function getAuthorizeUrl($platform)
    {
        $thirdPlatforms = Wekit::C('webThirdLogin');

        $config = Wekit::C()->getConfigByName('site', 'info.url');
        $redirecturl = $config['value'].'/index.php?m=u&c=login&a=thirdlogincallback&platform='.$platform;

        switch($platform) {
        case 'qq':
            return sprintf(self::$supportedPlatforms[$platform]['authorize'],
                           $thirdPlatforms[$platform.'.appid'],
                           urlencode($redirecturl)
                          );
        case 'weibo':
            return sprintf(self::$supportedPlatforms[$platform]['authorize'],
                           $thirdPlatforms[$platform.'.appid'],
                           urlencode($redirecturl)
                          );
        default:
            // should never happen
            return '';
        }
    }

    public function getAccessToken($platform, $authcode)
    {
        $thirdPlatforms = Wekit::C('webThirdLogin');
        $config = Wekit::C()->getConfigByName('site', 'info.url');

        $method = 'get';
        $redirecturl = $config['value'].'/index.php?m=u&c=login&a=thirdlogincallback&platform='.$platform;
        switch($platform) {
        case 'qq':
            $url = sprintf(self::$supportedPlatforms[$platform]['accesstoken'],
                           $thirdPlatforms[$platform.'.appid'],
                           $thirdPlatforms[$platform.'.appkey'],
                           $authcode,
                           urlencode($redirecturl)
                          );
            break;
        case 'weibo':
            $url = self::$supportedPlatforms[$platform]['accesstoken'];
            $postdata = array('client_id' => $thirdPlatforms[$platform.'.appid'],
                              'client_secret' => $thirdPlatforms[$platform.'.appkey'],
                              'code' => $authcode,
                              'redirect_uri' => $redirecturl,
                             );
            $method = 'post';
            break;
        default:
            // should never happen
            return array(false, '');
        }

        $data = $this->_request($url, ($method == 'post' ? $postdata : array()), $method);
        if (!$data) {
            return array(false, '');
        }
        switch($platform) {
        case 'qq':
            if (substr($data, 0, 8) == 'callback') {
                $result = json_decode(substr($data, 10, -4), true);
            } else {
                parse_str($data, $result);
            }
            if (isset($result['error'])) {
                return array(false, array($result['error'], $result['error_description']));
            } else {
                return array(true, $result['access_token'], 'extra' => array());
            }
        case 'weibo':
            $result = json_decode($data, true);
            if (isset($result['error_code']) && $result['error_code'] != 0) {
                return array(false, array($result['error_code'], $result['error']));
            }
            return array(true, $result['access_token'], 'extra' => array('uid' => $result['uid']));
        default:
            return array(false, '');
        }
    }

    public function getUserInfo($platform, $accesstoken, array $extra)
    {
        switch($platform) {
        case 'qq':
            $url = sprintf(self::$supportedPlatforms[$platform]['openid'],
                           $accesstoken
                          );
            break;
        default:
            break;
        }
        if (isset($url)) {
            $openid = $this->getOpenId($url);
            if (!$openid[0]) {
                return $openid;
            }
            $openid = $openid[1];
        } else {
            $openid = $extra['uid'];
        }

        $thirdPlatforms = Wekit::C('webThirdLogin');
        switch($platform) {
        case 'qq':
            $url = sprintf(self::$supportedPlatforms[$platform]['userinfo'],
                           $accesstoken,
                           $thirdPlatforms[$platform.'.appid'],
                           $openid
                          );
            break;
        case 'weibo':
            $url = sprintf(self::$supportedPlatforms[$platform]['userinfo'],
                           $accesstoken,
                           $openid
                          );
            break;
        default:
            break;
        }
        $data     = $this->_request($url, array());
        $userinfo = array();
        switch($platform) {
        case 'qq':
            $result = json_decode($data, true);
            if ($result['ret'] != 0) {
                $userinfo[0] = false;
                $userinfo[1] = array('code' => $result['ret'],
                                     'msg'  => $result['msg']
                                    );
            } else {
                $userinfo[0] = true;
                $uid = md5($result['figureurl_qq_2']);
                $userinfo[1] = array(
                        'uid'      => $uid,
                        'username' => $result['nickname'],
                        'gender'   => $result['gender'] == '男' ? 0 : 1,
                        'avatar'   => $result['figureurl_qq_2'],
                        'type'     => $platform,
                        'email'    => 'example'.substr($uid, 0, 10).'@qq.com',
                        );
            }
            return $userinfo;
        case 'weibo':
            $result = json_decode($data, true);
            if (isset($result['error_code']) && $result['error_code'] != 0) {
                $userinfo[0] = false;
                $userinfo[1] = array('code' => $result['error_code'],
                                     'msg'  => $result['error']
                                    );
            } else {
                $userinfo[0] = true;
                $userinfo[1] = array(
                        'uid'      => $result['id'],
                        'username' => substr($result['name'], 0, 15),
                        'gender'   => $result['gender'] == 'm' ? 0 : 1,
                        'avatar'   => $result['avatar_large'],
                        'type'     => $platform,
                        'email'    => 'example'.substr(md5($result['id']), 0, 10).'@weibo.com',
                        );
            }
            return $userinfo;
        default:
            return array(false, '');
        }
    }

    public function getOpenId($url)
    {
        $data = self::_request($url, $params);
        if (!$data) {
            return array(false, '');
        }
        $result = json_decode(substr($data, 10, -4), true);
        if (isset($result['error'])) {
            return array(false, array($result['error'], $result['error_description']));
        } else {
            return array(true, $result['openid']);
        }
    }

    // 发往外部的http请求超时时间
    const HTTP_TIMEOUT = 4;

    protected function _request($url, $params, $method = 'get')
    {
        $result = WindidUtility::buildRequest($url, $params, /* isreturn = */ true,
                                              self::HTTP_TIMEOUT, $method);
        return !empty($result) ? $result : false;
    }
}
