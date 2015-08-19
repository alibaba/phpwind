<?php

Wind::import('ADMIN:library.AdminBaseController');

class ForumController extends AdminBaseController {
    /* (non-PHPdoc)
     * @see WindController::run()
     */

    public function run() {
        $configs = Wekit::C()->getValues('native');
        /*
        $fids = $this->_getForumService()->fids;
        $fids = array_flip($fids);
         * 
         */
        $fids = isset($configs['forum.fids']) && $configs['forum.fids'] ? $configs['forum.fids'] : array();
        $life_fid = isset($configs['forum.life_fid']) && $configs['forum.life_fid'] ? $configs['forum.life_fid'] : 0;
        $fid_default = isset($configs['forum.fid.default']) && $configs['forum.fid.default'] ? intval($configs['forum.fid.default']) : 0;
        $forum_open = isset($configs['forum.open']) && $configs['forum.open'] ? intval($configs['forum.open']) : 0;
        //获取允许在移动端展示的一级板块
        $forums = Wekit::load('forum.PwForum')->getCommonForumList(PwForum::FETCH_MAIN | PwForum::FETCH_STATISTICS);
        $categories = array();
        $forums_life = Wekit::loadDao('native.dao.PwForumLifeDao')->fetchForumLifeList();//批量获取生活服务版块用于过滤
        
        $vieworder = array();
        foreach($forums as $v){
            $vieworder[] = $v['vieworder'];
        }
        array_multisort($vieworder,SORT_ASC, $forums);
        foreach($forums as $k=>$v){//过滤非分类、非1级版块、以及生活服务板块          
            if($v['type']=="category" && $v['fid']!=$life_fid){//分类
                $categories[$k] = $v;
                $categories[$k]['name'] = strip_tags($v['name']);
                unset($forums[$k]);
            }else if($v['type']=='forum' && !array_key_exists($v['fid'], $forums_life)){//一级版块
                $forums[$k]['name'] = strip_tags($v['name']);
                if(intval($v['fid']) === $fid_default){
                    $forums[$k]['isdefault'] = true;//移动端发帖默认版面
                }else{
                    $forums[$k]['isdefault'] = false;
                }
                if (array_key_exists($v['fid'], $fids)) {
                    $forums[$k]['checked'] = true;//允许在移动端展示的版面，并设置可见
                }else{
                    $forums[$k]['checked'] = false;//允许在移动端展示的版面，但不可见
                }          
            }else{//其他
                unset($forums[$k]);
            }
        }
//        var_dump($forums);exit;
        //格式化分类下的一级版块
        foreach($categories as $k=>$v){
            $categories[$k]['forums'] = array();
            foreach($forums as $forum_key=>$forum_value){
                if($v['fid']==$forum_value['parentid']){
                    $categories[$k]['forums'][$forum_key] = $forum_value;
                }
            }
        }
//        var_dump($categories);exit;
        /*
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
         * 
         */
        $this->setOutput($forums, 'forums');
        $this->setOutput($categories, 'categories');
        $this->setOutput($forum_open, 'forum_open');
    }
    
    public function doEditAction(){
        $config = new PwConfigSet('native');
        $forums = array();
        if(isset($_POST['forums']) && $_POST['forums']){
            foreach($_POST['forums'] as $v){
                isset($v['fid']) ? $forums[intval($v['fid'])] = intval($v['order']):"";
            }
        }
        $fid_default = isset($_POST['fid_default']) && $_POST['fid_default'] ? intval($_POST['fid_default']) : 0;
        $forum_open = isset($_POST['forum_open']) ? intval($_POST['forum_open']) : 0;
        
        $config->set('forum.fids',$forums)->set('forum.fid.default',$fid_default)->set('forum.open',$forum_open)->flush();
        $this->showMessage('success', 'native/forum/run/', true);
    }

    private function _getForumService(){    
        return Wekit::load('native.srv.PwForumService');
    }   

    
}
