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
}
