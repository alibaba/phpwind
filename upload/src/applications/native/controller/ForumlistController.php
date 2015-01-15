<?php

/**
 * 版块列表接口
 *
 * @fileName: ForumListController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
class ForumListController extends PwBaseController {

    public $todayposts = 0;
    public $article = 0;

    /**
     * 获取板块列表
     * @access public
     * @return string
     <pre>
     /index.php?m=native&c=forumlist
     response: 
     </pre>
     */
    public function run() {

        $forumList = $this->_getForumService()->fetchForum($this->_getForumService()->fids);

        $this->setOutput($forumList, $data);
        $this->showMessage('success');
    }
    
    private function _getForumService(){                                                                                           
        return Wekit::load('native.srv.PwForumService');
    }  
}
