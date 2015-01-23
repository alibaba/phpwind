<?php

/**
 * 版块列表相关
 *
 * @fileName: ForumListController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
Wind::import('APPS:native.controller.NativeBaseController');

class ForumListController extends NativeBaseController {

    public $todayposts = 0;
    public $article = 0;
    
    public function beforeAction($handlerAdapter) {
//        echo 111;exit;
        parent::beforeAction($handlerAdapter);
//        $this->checkUserSessionValid();
        $this->uid = 1;//测试uid
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
    }

    /**
     * 获取板块列表
     * @access public
     * @return string
     <pre>
     /index.php?m=native&c=forumlist
     response: 
     </pre>
     */
    /*
    public function run() {

        $forumList = $this->_getForumService()->fetchForum($this->_getForumService()->fids);

        $this->setOutput($forumList, $data);
        $this->showMessage('success');
    }
     * 
     */
    
    private function _getForumService(){                                                                                           
        return Wekit::load('native.srv.PwForumService');
    }
    
    
    /**
     * 获取板块列表
     * @access public
     * @return string
     <pre>
     /index.php?m=native&c=forumlist&_json=1
     response: 
     </pre>
     */
    public function run() {
//        var_dump($this->uid);exit;
        /* @var $forumDs PwForum */
        $forumDs = Wekit::load('forum.PwForum');
        $list = $forumDs->getCommonForumList(PwForum::FETCH_MAIN | PwForum::FETCH_STATISTICS);

        list($cateList, $forumList) = $this->_filterMap($list); //过滤掉不显示的版块
        $bbsinfo = Wekit::load('site.PwBbsinfo')->getInfo(1);
        $configs = Wekit::C()->getValues('native');
        $fids_native = isset($configs['forum.fids']) && $configs['forum.fids'] ? $configs['forum.fids'] : array(); //管理后台配置移动端可展示的一级版面
        $forum_list_native = $forum_list_user = $user_fids = $native_vieworder = $user_vieworder = array();
        if ($this->uid) {//此处涉及用户登录状态判断,获取用户关注的版块
            $forumUserDao = Wekit::loadDao('forum.dao.PwForumUserDao');
            $user_fids = $forumUserDao->getFroumByUid($this->uid);
        }

        foreach ($forumList as $cat_k => $cat_v) {
            foreach ($cat_v as $forum_k => $forum_v) {
                if (array_key_exists($forum_v['fid'], $fids_native)) {//判断版面是否是移动端可展示版面
                    if (array_key_exists($forum_v['fid'], $user_fids)) {//版面fid是用户关注的
                        $forum_list_user[$forum_k] = $forum_v;
                        $forum_list_user[$forum_k]['vieworder'] = $user_vieworder[] = $fids_native[$forum_k];
                        $forum_list_user[$forum_k]['isjoin'] = true;
                    } else {//版面fid不是用户关注的放到通用集合
                        $forum_list_native[$forum_k] = $forum_v;
                        $forum_list_native[$forum_k]['vieworder'] = $native_vieworder[] = $fids_native[$forum_k];
                        $forum_list_native[$forum_k]['isjoin'] = false;
                    }
                }
            }
        }
//        var_dump($forum_list_user,$forum_list_native);exit;
        $forum_list_user && array_multisort($user_vieworder, SORT_ASC, $forum_list_user);
        $forum_list_native && array_multisort($native_vieworder, SORT_ASC, $forum_list_native);
        $forum_list_merge = array_merge($forum_list_user, $forum_list_native);
//        var_dump($fids_native,$user_fids,$forum_list_merge);
        $data = array('user_info'=>array('uid'=>$this->uid),'forum_list'=>$forum_list_merge);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');        
    }

    /**
     * 过滤版块信息
     * 1、过滤掉不显示的版块
     *
     * @param array $list
     * @return array
     */
    private function _filterMap($list) {
        $cate = $forum = array();
        foreach ($list as $_key => $_item) {
            if (1 != $_item['isshow'])
                continue;
            $_item['manager'] = $this->_setManages(array_unique(explode(',', $_item['manager'])));
            if ($_item['parentid'] == 0) {
                $cate[$_key] = $_item;
                isset($forum[$_key]) || $forum[$_key] = array();
                $this->todayposts += $_item['todayposts'];
                $this->article += $_item['article'];
            } else {
                $forum[$_item['parentid']][$_key] = $_item;
            }
        }
        return array($cate, $forum);
    }

    /**
     * 设置版块的版主UID
     *
     * @param array $manage
     * @param array $userList
     * @return array
     */
    private function _setManages($manage) {
        $_manage = array();
        foreach ($manage as $_v) {
            if ($_v)
                $_manage[] = $_v;
        }
        return $_manage;
    }

}
