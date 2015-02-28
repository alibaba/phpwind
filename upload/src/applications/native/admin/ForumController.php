<?php

Wind::import('ADMIN:library.AdminBaseController');

class ForumController extends AdminBaseController {
    /* (non-PHPdoc)
     * @see WindController::run()
     */

    public function run() {
        $configs = Wekit::C()->getValues('native');

        $fids = $this->_getForumService()->fids;
        $fids = array_flip($fids);
        //$fids = isset($configs['forum.fids']) && $configs['forum.fids'] ? $configs['forum.fids'] : array();
        //
        $fid_default = isset($configs['forum.fid.default']) && $configs['forum.fid.default'] ? intval($configs['forum.fid.default']) : 0;

        //获取允许在移动端展示的一级板块
        $forums = Wekit::load('forum.PwForum')->getCommonForumList(PwForum::FETCH_MAIN | PwForum::FETCH_STATISTICS);
        $forums_life = Wekit::loadDao('native.dao.PwForumLifeDao')->fetchForumLifeList();//批量获取生活服务版块用于过滤
        
        foreach($forums as $k=>$v){//过滤非1级版块以及生活服务板块
            if($v['type']!='forum' || array_key_exists($k, $forums_life)) unset($forums[$k]);
        }

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
        array_multisort($orders,SORT_ASC, $forums);
        $forums = array_merge($forums,$forums_tmp);
        $this->setOutput($forums, 'forums');
    }
    
    public function doEditAction(){
        $config = new PwConfigSet('native');
        $forums = array();
        if(isset($_POST['forums']) && $_POST['forums']){
            foreach($_POST['forums'] as $v){
                $forums[intval($v['fid'])] = intval($v['order']);
            }
        }
        $fid_default = isset($_POST['fid_default']) && $_POST['fid_default'] ? intval($_POST['fid_default']) : 0;
        $config->set('forum.fids',$forums)->set('forum.fid.default',$fid_default)->flush();
        
        $this->showMessage('success', 'native/forum/run/', true);
    }

    private function _getForumService(){    
        return Wekit::load('native.srv.PwForumService');
    }   

    
}
