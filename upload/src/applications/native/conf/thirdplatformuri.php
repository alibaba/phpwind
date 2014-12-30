<?php
/**
 * 第三账帐号开放平台的请求地址配置
 *
 * @fileName: openaccountapi.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-18 17:31:10
 * @desc:   
 **/
defined('WEKIT_VERSION') or exit(403);

return array(
    //需要2步
    'weixin'=>array(
        /**
         * 通过code获取access_token的接口        
         * { 
         * "access_token":"ACCESS_TOKEN", 
         * "expires_in":7200, 
         * "refresh_token":"REFRESH_TOKEN",
         * "openid":"OPENID", 
         * "scope":"SCOPE" 
         *  }
         */
        'access_token_uri'=>'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code&redirect_uri=%s',
        // 检验授权凭证（access_token）是否有效
        'userinfo_uri'=>'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s',
    ),
    'qq'=>array(
        'access_token_uri'=>'https://graph.qq.com/oauth2.0/token?client_id=%s&client_secret=%s&code=%s&grant_type=authorization_code&redirect_uri=%s',
        /**
         * 获得openid
         * callback( {"client_id":"YOUR_APPID","openid":"YOUR_OPENID"}  );
         */
        'openid'=>'https://graph.qq.com/oauth2.0/me?access_token=%s',
        'userinfo_uri'=>'https://graph.qq.com/user/get_user_info?access_token=%s&oauth_consumer_key=%s&openid=%s&format=json',
    ),
    'weibo'=>array(
        'access_token_uri'=>'https://api.weibo.com/oauth2/access_token?client_id=%s&client_secret=%s&code=%s&grant_type=authorization_code&redirect_uri=%s',
        'userinfo_uri'=>'https://api.weibo.com/2/users/show.json?access_token=%s&uid=%s&source=%s&trim_user=0',
    ),
    'taobao'=>array(
        //'access_token_uri'=>'https://oauth.tbsandbox.com/token?client_id=%s&client_secret=%s&code=%s&grant_type=authorization_code&redirect_uri=%s',
        'access_token_uri'=>'https://oauth.taobao.com/token?client_id=%s&client_secret=%s&code=%s&grant_type=authorization_code&redirect_uri=%s',
        'userinfo_uri'=>'http://gw.api.taobao.com/router/rest?session=%s&sign=%s&timestamp=%s&v=2.0&app_key=1012129701&method=taobao.user.buyer.get&partner_id=top-apitools&format=json&fields=nick%2Csex%2Cavatar',
    ),
);

