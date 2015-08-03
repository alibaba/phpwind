<?php
/**
 * 发帖、回复帖子回复回帖、针对某一个楼层的回复列表相关接口
 *
 * @fileName: PostController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');
Wind::import('SRV:forum.srv.PwPost');
Wind::import('WIND:utility.WindJson');
Wind::import('SRV:credit.bo.PwCreditBo');
Wind::import('APPS:native.controller.NativeBaseController');

class PostController extends NativeBaseController {
    public $post;
    protected $perpage = 10;
    protected $loginUser;

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
        $action = $handlerAdapter->getAction();
        
        //非法请求
        if (in_array($action, array('fastreply', 'replylist'))) {
            return;
        }
        $this->post = $this->_getPost($action);
        $this->post->user = $this->loginUser;        

        if (($result = $this->post->check()) !== true) {
            $error = $result->getError();
            if (is_array($error) && $error[0] == 'BBS:post.forum.allow.ttype' && ($allow = $this->post->forum->getThreadType($this->post->user))) {
                $special = key($allow);
                //
            }
            $this->showError($error);
        }

/*
        //版块风格
        $pwforum = $this->post->forum;
        if ($pwforum->foruminfo['password']) {
            if (!$this->uid) {
                $this->forwardAction('u/login/run', array('backurl' => WindUrlHelper::createUrl('bbs/post/' . $action, array('fid' => $$pwforum->fid))));
            } elseif (Pw::getPwdCode($pwforum->foruminfo['password']) != Pw::getCookie('fp_' . $pwforum->fid)) {
                $this->forwardAction('bbs/forum/password', array('fid' => $pwforum->fid));
            }
        }
        if ($pwforum->foruminfo['style']) {
            $this->setTheme('forum', $pwforum->foruminfo['style']);
        }

        $this->setOutput($action, 'action');
 */
    }

    /**
     * 发布帖子
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=post&a=doadd&fid=分类id
     //post: atc_title=测试title&atc_content=测试内容&tagnames=测试话题&pid=默认空&tid=默认空&special=default&reply_notice=1
     post: atc_content=#话题#测试内容&pid=默认空&tid=默认空&special=default&reply_notice=1&flashatt[43][desc]=描述1&flashatt[44][desc]=描述2
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */
    public function doAddAction() {

        //app发帖子不带标题,内容格式化，抓取分享链接内容，此处尚需要处理
        list($title, $content, $topictype, $subtopictype, $reply_notice, $hide, $created_address,$area_code,$share_url) = $this->getInput(array('atc_title', 'atc_content', 'topictype', 'sub_topictype', 'reply_notice', 'hide' ,'created_address','area_code','share_url'), 'post');
        $from_type = $title ? 0 : 1;//0 移动端发帖带标题（同pc）；1 移动端发帖不带标题
        //从内容中获得tag
        preg_match("/^(#[^#]+?#)?(.+)/i",$content, $matches);
        if( isset($matches[1]) && $matches[1] ){
            isset($matches[1]) && $_POST['tagnames'] = explode('#', trim($matches[1],'#'));
            isset($matches[2]) && $content = $matches[2];
        }
        $this->runHook('c_post_run', $this->post);
        $this->runHook('c_post_doadd', $this->post);//PwHook.php 组件调用机制完成附件上传、话题添加
        // 
//        $title = preg_match('/\[[^\]]+\]/i',$content);
//        $title = mb_substr(strip_tags($content), 0,15,"UTF-8");
        $title || $title = Pw::substrs(preg_replace("/\[.*?\]/i","",$content), 15,0,false);
        $title ? "" : $title="来自移动端的帖子";
        $postDm = $this->post->getDm();
        $postDm->setTitle($title)
            ->setContent($content)
            ->setHide($hide)
            ->setReplyNotice($reply_notice);
        //set topic type
        $topictype_id = $subtopictype ? $subtopictype : $topictype;
        $topictype_id && $postDm->setTopictype($topictype_id);
        if (($result = $this->post->execute($postDm)) !== true) {
            $data = $result->getData();
            $data && $this->addMessage($data, 'data');
            $this->showError($result->getError());
        }
        $tid = $this->post->getNewId();
        //在帖子移动端扩展表中插入数据
        $data = array('tid'=>$tid,'from_type'=>$from_type,'created_address'=>$created_address,'area_code'=>$area_code);
        $res = Wekit::loadDao('native.dao.PwThreadsPlaceDao')->insertValue($data);
        $this->showMessage('success');
    }

    /**
     * 回复帖子；回复回帖；回复回复
     * @access public
     * @return string
     * @example
     <pre>
     直接回复时参数状态：/index.php?m=native&c=post&a=doreply&_getHtml=1
     点击喜欢后顺便回复时参数状态：/index.php?m=native&c=post&a=doreply&fid=分类id
     ( _getHtml: 1表示回复帖子；2表示回复回帖 | )
     post(回复帖子): tid&atc_content&created_address&area_code
     post(回复回帖、回复回复->相当于在本楼层回帖): tid&pid&atc_content&created_address&area_code
     post(在点喜欢的时候顺便回复内容)：tid&pid&atc_content&created_address&area_code&from_type=like
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */
    public function doreplyAction() {
        $tid = $this->getInput('tid');
        list($title, $content, $hide, $rpid,$created_address,$area_code) = $this->getInput(array('atc_title', 'atc_content', 'hide', 'pid' ,'created_address' ,'area_code'), 'post');
        $_getHtml = $this->getInput('_getHtml', 'get');
        $this->runHook('c_post_run', $this->post);
        $this->runHook('c_post_doreply', $this->post);

        $info = $this->post->getInfo();
        $title == 'Re:' . $info['subject'] && $title = '';
        if ($rpid) {//回复一个回帖
            $post = Wekit::load('thread.PwThread')->getPost($rpid);
            if ($post && $post['tid'] == $tid && $post['ischeck']) {
                $post['content'] = $post['ifshield'] ? '此帖已被屏蔽' : trim(Pw::stripWindCode(preg_replace('/\[quote(=.+?\,\d+)?\].*?\[\/quote\]/is', '', $post['content'])));
                $post['content'] && $content = '[quote=' . $post['created_username'] . ',' . $rpid . ']' . Pw::substrs($post['content'], 120) . '[/quote] ' . $content;
            } else {//回复主贴
                $rpid = 0;
            }
        }
        
        $postDm = $this->post->getDm();
        $postDm->setTitle($title)
            ->setContent($content)
            ->setHide($hide)
            ->setReplyPid($rpid);

        if (($result = $this->post->execute($postDm)) !== true) {
            $data = $result->getData();
            $data && $this->addMessage($data, 'data');
            $this->showError($result->getError());
        }
        $pid = $this->post->getNewId();
        //记录回帖位置信息
        $data = array('pid'=>$pid,'created_address'=>$created_address,'area_code'=>$area_code);
        $res = Wekit::loadDao('native.dao.PwPostsPlaceDao')->insertValue($data);

        // 发送通知
        // 关于type请查看sendNotification的注释
        // 如果自己回复了自己的帖子，则不发送通知
        if ($info['created_userid'] != $this->uid) {
            PwLaiWangSerivce::sendNotification($info['created_userid'],
                array('type' => ($rpid > 0 ? 3 : 2),
                      'message' => ($rpid > 0 ?
                              $this->loginUser->info['username']." 评论了您的回帖：\n".$content
                            : $this->loginUser->info['username'].' 评论了您的帖子《'.$info['subject']."》：\n".$content),
                      'url' => ($rpid > 0 ? 'read' : 'read'),
                      'arg' => ($rpid > 0 ? array((string)$tid)
                                : array((string)$tid)),
                )
            );
        }

        //
        $this->showMessage('success');
    }


    /**
     * 针对某一个楼层的简略回复列表，过滤附件内容，带翻页（不传分页参数默认展示3条）
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=post&a=replylist&tid=33&pid=33&page=2&floor=当前楼层号
     response: {err:"",data:""}  
     </pre>
     */
    public function replylistAction(){
        $this->_replylist();
    }


    private function _getPost($action) {
        switch ($action) {
        case 'reply':
        case 'doreply':
            $tid = $this->getInput('tid');
            Wind::import('SRV:forum.srv.post.PwReplyPost');
            $postAction = new PwReplyPost($tid, $this->loginUser);
            break;
        /*
        case 'modify':
        case 'domodify':
            $tid = $this->getInput('tid');
            $pid = $this->getInput('pid');
            if ($pid) {
                Wind::import('SRV:forum.srv.post.PwReplyModify');
                $postAction = new PwReplyModify($pid);
            } else {
                Wind::import('SRV:forum.srv.post.PwTopicModify');
                $postAction = new PwTopicModify($tid);
            }
            break;
        */
        default:
            $fid = $this->getInput('fid');
            $special = $this->getInput('special');
            Wind::import('SRV:forum.srv.post.PwTopicPost');
            $postAction = new PwTopicPost($fid, $this->loginUser);
            $special && $postAction->setSpecial($special);
        }
        return new PwPost($postAction);
    }

    private function _replylist() {

        list($tid, $pid, $page, $simple) = $this->getInput(array('tid', 'pid', 'page', 'simple'), 'get');
        $page = intval($page);
        $perpage = $page ? $this->perpage : 3;//没有分页参数，默认展示3条针对一个楼层的回复
        !$page && $page = 1;
        
        //$info = Wekit::load('forum.PwThread')->getThread($tid);
        $replydb = $data = array();
        if ($pid) {
            $reply = Wekit::load('forum.PwThread')->getPost($pid);
            $total = $reply['replies'];

            list($start, $limit) = Pw::page2limit($page, $perpage);
            $replydb = Wekit::load('forum.PwPostsReply')->getPostByPid($pid, $limit, $start);
//            $replydb = Wekit::load('forum.srv.PwThreadService')->displayReplylist($replydb,140,false);//不对回复内容截字
            //
            $replyList = array();
            foreach ($replydb as $key=>$v) {
                $replyList[$key] = array(
                    'fid'               =>$v['fid'],
                    'tid'               =>$v['tid'],
                    'pid'               =>$v['pid'],
                    'created_time'      =>Pw::time2str($v['created_time'],'auto'),
                    'created_username'  =>$v['created_username'],
                    'created_userid'    =>$v['created_userid'],
                    'content'           =>preg_replace('/\[quote.*?\].+?\[\/quote\]/i','',$v['content']),
                );
                if( $simple && mb_strlen($replyList[$key]['content'],'utf-8') > 30){
                    $replyList[$key]['content'] = mb_substr($replyList[$key]['content'], 0, 30,'utf-8')."...";
                }
            }
            $data = array(
                'total'     =>(int)$total,
                'perpage'   =>$perpage,
                'pageCount' =>ceil($total/$perpage),
                'replyList' =>$replyList,

            );
        } 
        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }
}
