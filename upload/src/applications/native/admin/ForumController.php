<?php

Wind::import('ADMIN:library.AdminBaseController');

class ForumController extends AdminBaseController {
    /* (non-PHPdoc)
     * @see WindController::run()
     */

    public function run() {
//        var_dump($GLOBALS);
//        exit;
        $configs = Wekit::C()->getValues('native');
        $fids = isset($configs['forum.fids']) && $configs['forum.fids'] ? $configs['forum.fids'] : array();
        $fid_default = isset($configs['forum.fid.default']) && $configs['forum.fid.default'] ? intval($configs['forum.fid.default']) : 0;
//        var_dump($fids,$fid_default);exit;
        /*
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
//        $sql = "SELECT f.`fid`,f.`name`,IFNULL(fn.`isdefault`,-1) isdefault 
//                FROM `pw_bbs_forum` f 
//                LEFT JOIN `pw_bbs_forum_native` fn 
//                ON f.`fid`=fn.`fid` 
//                WHERE f.`hassub`=0;";
        $sql = "SELECT `fid`,`name`
                FROM `${prefix}bbs_forum` 
                WHERE `hassub`=0;";
        $forums = $dao->fetchAll($sql);
         * 
         */
        //获取允许在移动端展示的一级板块
        $forums = Wekit::load('forum.PwForum')->getCommonForumList(PwForum::FETCH_MAIN | PwForum::FETCH_STATISTICS);
        $forums_life = Wekit::loadDao('native.dao.PwForumLifeDao')->fetchForumLifeList();//批量获取生活服务版块用于过滤
        
//        var_dump($forums,$forums_life);exit;
        foreach($forums as $k=>$v){//过滤非1级版块以及生活服务板块
            if($v['type']!='forum' || array_key_exists($k, $forums_life)) unset($forums[$k]);
        }
//        var_dump($forums,$forums_life);exit;
//        $forumSrv = Wekit::load('native.srv.PwForumService');
//        $forums = $forumSrv->getFormList();
        $orders = array();
        $forums_tmp = array();
        foreach($forums as $k=>$v){//从版块列表中筛选归类
            if(intval($v['fid']) === $fid_default){
                $forums[$k]['isdefault'] = 1;//移动端发帖默认版面
                $forums[$k]['order'] = $fids[$v['fid']];
                $orders[] = $forums[$k]['order'];
            }  elseif (array_key_exists($v['fid'], $fids)) {
                $forums[$k]['isdefault'] = 0;//允许在移动端展示的版面，并设置可见
                $forums[$k]['order'] = $fids[$v['fid']];
                $orders[] = $forums[$k]['order'];
            }  else {
                $forums[$k]['isdefault'] = -1;//允许在移动端展示的版面，但不可见
                $forums[$k]['order'] = '';
                $forums_tmp[] = $forums[$k];
                unset($forums[$k]);
            }
            
        }
//        var_dump($forums,$forums_tmp);exit;
        array_multisort($orders,SORT_ASC, $forums);
        $forums = array_merge($forums,$forums_tmp);
//        var_dump($forums,$forums_tmp);exit;
//        var_dump($res);exit;
        $this->setOutput($forums, 'forums');
//        var_dump($forums);exit;
    }
    
    public function doEditAction(){
//        var_dump($_POST);exit;
        $config = new PwConfigSet('native');
//        $config->set('forum.fids',array(1,2,4))->set('forum.fid.default',1)->flush();
        $forums = array();
        if(isset($_POST['forums']) && $_POST['forums']){
            foreach($_POST['forums'] as $v){
                $forums[intval($v['fid'])] = intval($v['order']);
            }
        }
//        var_dump($forums);exit;
        $fid_default = isset($_POST['fid_default']) && $_POST['fid_default'] ? intval($_POST['fid_default']) : 0;
        $config->set('forum.fids',$forums)->set('forum.fid.default',$fid_default)->flush();
        
        $this->showMessage('success', 'native/forum/run/', true);
    }

    
}
