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
class PwThirdLoginService
{
    public static $supportedPlatforms = array(
        // See http://wiki.open.qq.com/wiki/website/%E4%BD%BF%E7%94%A8Authorization_Code%E8%8E%B7%E5%8F%96Access_Token
        'qq'    => array(
            'img'       => 'http://qzonestyle.gtimg.cn/qzone/vas/opensns/res/img/bt_blue_76X24.png',
            'authorize' => 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=%s&redirect_uri=%s&state=phpwind&scope=get_user_info',
            'accesstoken' => 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=%s&client_secret=%s&code=%s&redirect_uri=%s',
        ),
        'weibo' => array(
            'img' => '',
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
            $c['url']    = '/index.php?m=u&c=login&a=thirdlogin&platform='.$p;
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
        default:
            // should never happen
            return '';
        }
    }

    public function getAccessToken($platform, $authcode)
    {
        $thirdPlatforms = Wekit::C('webThirdLogin');
        $config = Wekit::C()->getConfigByName('site', 'info.url');

        $redirecturl = $config['value'].'/index.php?m=u&c=login&a=thirdlogincallback&platform='.$platform;
        switch($platform) {
        case 'qq':
            $url = sprintf(self::$supportedPlatforms[$platform]['accesstoken'],
                           $thirdPlatforms[$platform.'.appid'],
                           $thirdPlatforms[$platform.'.appkey'],
                           $authcode,
                           urlencode($redirecturl)
                          );
        default:
            // should never happen
            return array(false, '');
        }

        $data = $this->_request($url, array());
        if (!$data) {
            return array(false, '');
        }
        switch($platform) {
        case 'qq':
            parse_str($data, $result);
            if (isset($result['code'])) {
                return array(false, array($result['code'], $result['msg']));
            } else {
                return array(true, $result['access_token']);
            }
        default:
            return array(false, '');
        }
    }

    // 发往外部的http请求超时时间
    const HTTP_TIMEOUT = 4;

    protected function _request($url, $params)
    {
        $result = WindidUtility::buildRequest($url, $params, /* isreturn = */ true,
                                              self::HTTP_TIMEOUT, 'get');
        return !empty($result) ? $result : false;
    }
}
