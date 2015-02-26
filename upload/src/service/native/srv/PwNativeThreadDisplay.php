<?php
/**
 * @fileName: PwNativeThreadDisplay.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-23 14:32:18
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.srv.PwThreadDisplay');

class PwNativeThreadDisplay extends PwThreadDisplay {

    public function __construct($tid, PwUserBo $user) {
        parent::__construct($tid, $user);
    }

    protected function _bulidContent($read) {
        $_content = self::escapeSpace($read['content']);
        
        //检查是否购买过
        $threadBuy = Wekit::load('forum.PwThreadBuy')->getByTidAndUid($this->tid, $this->user->uid);
        if( $threadBuy && isset($threadBuy[$read['pid']]) || $this->user->uid==$read['created_userid'] ){
            //todo
        }else{
            $_content = preg_replace("/\[sell=(\d+),(\d+)\]([^\[]+)\[\/sell\]/", '[sell=\\1,\\2]此段为出售的内容，购买后显示[/sell]', $_content);
        }

        //检查回复后显示
        if ($read['created_userid'] == $this->user->uid) {
            //todo
        } elseif (Wekit::load('forum.PwThread')->countPostByTidAndUid($this->tid, $this->user->uid) > 0) {
            //todo
        } else {
            $_content = preg_replace("/\[post\]([^\[]+)\[\/post\]/", '[post]隐藏内容,回复后显示[/post]', $_content);
        } 

        //达到积分上限查看贴子
        $_content = preg_replace("/\[hide=(.+?)\](.+?)\[\/hide\]/eis","self::createHide('\\1', '\\2', '".$this->user->info['credit1']."')", $_content);


        return $_content;
    }

    protected static function createHide( $costs, $message, $user_credit1 ){
        list($cost, $credit) = explode(',', $costs);

        if( (int)$cost>=intval($user_credit1) ){
            return "[hide={$costs}]内容加密,需要{$cost}积分以上才能浏览[/hide]";
        }else{
            return "[hide={$costs}]{$message}[/hide]";
        }
    }

}
