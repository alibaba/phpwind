<?php
/**
 * @fileName: PwBanner.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-29 16:11:15
 * @desc: 
 **/

class PwBanner {

    const CONFIG_NAMESPACE = 'nativeBanner';

    public $fileds = array('title','clickType','path','link','order');

    public function getData() {
        $config = $this->_getConfig();
        //
        $configData = array();
        foreach($config as $k=>$v){
            list($key, $pos) = explode('.',$k);
            if( $key=='clickType'  ){
                $clickType = '';
                switch($v){
                case 'tag':
                    $clickType='话题';
                    break;
                case 'forum':
                    $clickType='论坛版面';
                    break;
                case 'topic':
                    $clickType='贴子';
                    break;
                case 'link':
                    $clickType='外部链接';
                    break;
                }
                $configData[$pos]['clickTypeValue'] = $clickType;
                $configData[$pos][$key] = $v;
                continue;
            }
            if( $key=='path'  ){
                $configData[$pos]['img']=Pw::getPath($data['path']).$v;
            }
            $configData[$pos][$key] = $v;
        }
        return $configData;
    }


    public function _getConfig(){
        $config = Wekit::C()->getValues(self::CONFIG_NAMESPACE);
        return $config;
    }

}
