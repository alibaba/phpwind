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
        'qq'    => array(
            'img' => 'http://qzonestyle.gtimg.cn/qzone/vas/opensns/res/img/bt_blue_76X24.png',
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
            $c['url']    = '/index.php?m=u&c=login&a=thirdLogin&platform='.$p;
            $platforms[] = $c;
        }

        return $platforms;
    }
}
