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

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $this->uid = 3; //测试uid
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
        $action = $handlerAdapter->getAction();

        if (in_array($action, array('fastreply', 'replylist'))) {
            return;
        }
        $this->post = $this->_getPost($action);
        /*
        if (($result = $this->post->check()) !== true) {
            $error = $result->getError();
            if (is_array($error) && $error[0] == 'BBS:post.forum.allow.ttype' && ($allow = $this->post->forum->getThreadType($this->post->user))) {
                $special = key($allow);
                $this->forwardAction('bbs/post/run?fid=' . $this->post->forum->fid . ($special ? ('&special=' . $special) : ''));
            }
            $this->showError($error);
        }
        */
        
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
    }

    /**
     * 发帖子的表单(测试用)
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=post&a=threadform
     cookie:usersession
     response: html
     </pre>
     */
    public function threadFormAction(){}
    
    /**
     * 回复帖子表单(测试用)
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=post&a=threadform
     cookie:usersession
     response: html
     </pre>
     */
    public function replyFormAction(){}
    
    /**
     * 回复回帖表单(测试用)
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=post&a=threadform
     cookie:usersession
     response: html
     </pre>
     */
    public function replyForm2Action(){}
    
    
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
        $pwPost = $this->post;
//        var_dump($_POST);exit;
//        $content = "#dfere#aaadsdghj#gdad#sdsd";
//        $_POST['tagnames'] = array('群发话题11','群发话题22','群发话题33');
        preg_match("/^(#[^#]+?#)?(.+)/i",$content, $matches);
        isset($matches[1]) && $_POST['tagnames'] = explode('#', trim($matches[1],'#'));
        isset($matches[2]) && $content = $matches[2];
//        var_dump($_POST['tagnames'],$content);exit;
        $this->runHook('c_post_doadd', $pwPost);//PwHook.php 组件调用机制完成附件上传、话题添加
        /*
        //抓取分享链接内容
        $options = array(
                            CURLOPT_HEADER => 1,
                            CURLOPT_URL => $share_url,
                            CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11",
                            CURLOPT_FRESH_CONNECT => 1,
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_REFERER, 'http://google.com/',
                            CURLOPT_FORBID_REUSE => 1,
                            CURLOPT_TIMEOUT => 15,
                            //CURLOPT_POSTFIELDS => http_build_query($post)
                        );
        $ch = curl_init();
        curl_setopt_array($ch, ($options));
        if( ! $share_content = curl_exec($ch))
        {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
//        var_dump($share_content);exit;
//        $share_content=file_get_contents($share_url);
        $encode = mb_detect_encoding($share_content, array('ASCII','GB2312','GBK','UTF-8'));//mb_internal_encoding()
        $encode == 'CP936' && $encode = 'GBK';
        $str = mb_convert_encoding($share_content, "UTF-8", $encode);
//        header("Content-Type: text/html; charset=UTF-8");
//        var_dump($encode,$str);exit;
         * 
         */
//        header("Content-Type: text/html; charset=UTF-8");
        $title = mb_substr(strip_tags($content), 0,15,"UTF-8");
        $postDm = $pwPost->getDm();
        $postDm->setTitle($title)
                ->setContent($content)
                ->setHide($hide)
                ->setReplyNotice($reply_notice);
//        var_dump($pwPost);
//        echo '---------------------------------------------------';
//        var_dump($postDm);
//        exit;
        //set topic type
        $topictype_id = $subtopictype ? $subtopictype : $topictype;
        $topictype_id && $postDm->setTopictype($topictype_id);

        if (($result = $pwPost->execute($postDm)) !== true) {
            $data = $result->getData();
            $data && $this->addMessage($data, 'data');
            $this->showError($result->getError());
        }
        $tid = $pwPost->getNewId();
        //在帖子移动端扩展表中插入数据
        $data = array('tid'=>$tid,'from_type'=>1,'created_address'=>$created_address,'area_code'=>$area_code);
        $res = Wekit::loadDao('native.dao.PwThreadsPlaceDao')->insertValue($data);
        $this->showMessage('success', 'bbs/read/run/?tid=' . $tid . '&fid=' . $pwPost->forum->fid, true);

        /*
          Array
          (
          [atc_title] => 测试发帖
          [atc_content] => 测试广告

          [pid] =>
          [tid] =>
          [special] => default
          [reply_notice] => 1
          [csrf_token] => b8b6b23262caeb01
          )


         */
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
        $pwPost = $this->post;
        $this->runHook('c_post_doreply', $pwPost);

        $info = $pwPost->getInfo();
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

        $postDm = $pwPost->getDm();
        $postDm->setTitle($title)
                ->setContent($content)
                ->setHide($hide)
                ->setReplyPid($rpid);

        if (($result = $pwPost->execute($postDm)) !== true) {
            $data = $result->getData();
            $data && $this->addMessage($data, 'data');
            $this->showError($result->getError());
        }
        $pid = $pwPost->getNewId();
        //记录回帖位置信息
        $data = array('pid'=>$pid,'created_address'=>$created_address,'area_code'=>$area_code);
        $res = Wekit::loadDao('native.dao.PwPostsPlaceDao')->insertValue($data);
        $this->showMessage('success', 'bbs/read/run/?tid=' . $tid . '&fid=' . $pwPost->forum->fid, true);
        exit;
//        var_dump($pid);exit;
        
        //页面输出部分与移动端无关
        if ($_getHtml == 1) {//回复帖子
            Wind::import('SRV:forum.srv.threadDisplay.PwReplyRead');
            Wind::import('SRV:forum.srv.PwThreadDisplay');
            $threadDisplay = new PwThreadDisplay($tid, $this->loginUser);
            $this->runHook('c_post_replyread', $threadDisplay);
            $dataSource = new PwReplyRead($tid, $pid);
            $threadDisplay->execute($dataSource);
            $_cache = Wekit::cache()->fetch(array('level', 'group_right'));

            $this->setOutput($threadDisplay, 'threadDisplay');
            $this->setOutput($tid, 'tid');
            $this->setOutput($threadDisplay->fid, 'fid');
            $this->setOutput($threadDisplay->getThreadInfo(), 'threadInfo');
            $this->setOutput(current($threadDisplay->getList()), 'read');
            $this->setOutput($threadDisplay->getUsers(), 'users');
            $this->setOutput($threadDisplay->getArea(), 'area');
            $this->setOutput($threadDisplay->getForum(), 'pwforum');
            $this->setOutput(PwCreditBo::getInstance(), 'creditBo');
            $this->setOutput(Wekit::C('bbs', 'read.display_member_info'), 'displayMemberInfo');
            $this->setOutput(Wekit::C('bbs', 'read.display_info'), 'displayInfo');

            $this->setOutput($_cache['level']['ltitle'], 'ltitle');
            $this->setOutput($_cache['level']['lpic'], 'lpic');
            $this->setOutput($_cache['level']['lneed'], 'lneed');
            $this->setOutput($_cache['group_right'], 'groupRight');

            $this->setTemplate('read_floor');
        } elseif ($_getHtml == 2) {//回复回帖
            $content = Wekit::load('forum.srv.PwThreadService')->displayContent($content, $postDm->getField('useubb'), $postDm->getField('reminds'));
            $this->setOutput($postDm->getField('ischeck'), 'ischeck');
            $this->setOutput($content, 'content');
            $this->setOutput($this->loginUser->uid, 'uid');
            $this->setOutput($this->loginUser->username, 'username');
            $this->setOutput($pid, 'pid');
            $this->setOutput(Pw::getTime() - 1, 'time');
            $this->setTemplate('read_reply_floor');
        } else {
            $this->showMessage('success', 'bbs/read/run/?tid=' . $tid . '&fid=' . $pwPost->forum->fid . '&page=e#' . $pid, true);
        }
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
                $postAction = new PwReplyPost($tid);
                break;
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
            default:
                $fid = $this->getInput('fid');
                $special = $this->getInput('special');
                Wind::import('SRV:forum.srv.post.PwTopicPost');
                $postAction = new PwTopicPost($fid);
                $special && $postAction->setSpecial($special);
        }
        return new PwPost($postAction);
    }

    private function _replylist() {
        /*
            $replydb = Wekit::load('forum.PwPostsReply')->getPostByPid(126, 5, 0);//获得回帖的回复列表
        //        $post = Wekit::load('forum.PwThread')->getPost(126);//获得回帖信息
        $post = Wekit::load('forum.PwThread')->getPost(133);//获得回帖信息，缺少用户头像
        $atts = Wekit::load('attach.PwThreadAttach')->fetchAttachByTid(array(87));
        $atts = Wekit::load('attach.PwThreadAttach')->fetchAttachByTidAndPid(array(87), array(129));
        $atts = Wekit::load('native.PwNativeThread')->getThreadAttach(array(87), array(0));
        //        var_dump($atts);exit;
        var_dump($post,$replydb);exit;
        * 
         */

        list($tid, $pid, $page, $simple) = $this->getInput(array('tid', 'pid', 'page', 'simple'), 'get');
        $page = intval($page);
        !$page && $page = 1;
        $perpage = $page ? $this->perpage : 10;//没有分页参数，默认展示3条针对一个楼层的回复

        //$info = Wekit::load('forum.PwThread')->getThread($tid);
        $replydb = $data = array();
        if ($pid) {
            $reply = Wekit::load('forum.PwThread')->getPost($pid);
            $total = $reply['replies'];
            
/*
            list($start, $limit) = Pw::page2limit($page, $perpage);
            //Wind::import('LIB:ubb.PwSimpleUbbCode');
            //Wind::import('LIB:ubb.config.PwUbbCodeConvertThread');
            $replydb = Wekit::load('forum.PwPostsReply')->getPostByPid($pid, $limit, $start);//回帖的回复，内容未格式化
            $replydb = Wekit::load('forum.srv.PwThreadService')->displayReplylist($replydb);//对回帖回复内容进行格式化并截字处理，简略回复不展示附件及图片
            if($page == 1 && $perpage == $this->perpage){
                $reply['created_time'] = Pw::time2str($reply['created_time'],'auto');//格式化时间
                $reply['avatar'] = Pw::getAvatar($reply['created_userid'],'small');//获取头像
            }else{
                $reply = array();
            }
            $page_info = array('page'=>$page,'perpage'=>$perpage,'count'=>$total,'max_page'=>  ceil($total/$perpage));
            $data = array('page_info'=>$page_info,'tid'=>$info['tid'],'post'=>$reply,'reply_list'=>$replydb);
            */
            list($start, $limit) = Pw::page2limit($page, $perpage);
            $replydb = Wekit::load('forum.PwPostsReply')->getPostByPid($pid, $limit, $start);
            $replydb = Wekit::load('forum.srv.PwThreadService')->displayReplylist($replydb);

            //
            $replyList = array();
            foreach ($replydb as $key=>$v) {
                $replyList[$key] = array(
                    'created_time'      =>Pw::time2str($reply['created_time'],'auto'),
                    'created_username'  =>$v['created_username'],
                    'content'           =>$v['content'],
                );
                if( $simple ){
                    $replyList[$key]['content'] = mb_substr($replyList[$key]['content'], 0, 30);
                }
            }
            $data = array(
                'total'     =>(int)$total,
                'perpage'   =>$perpage,
                'pageCount' =>ceil($total/$perpage),
                'replyList' =>$replyList,

            );
//            print_r($data);
        } 
        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }

    private function _initVar() {
        $creditBo = PwCreditBo::getInstance();
        $sellCreditRange = $this->loginUser->getPermission('sell_credit_range', false, array());
        $allowThreadExtend = $this->loginUser->getPermission('allow_thread_extend', false, array());
        $sellConfig = array(
            'ifopen' => ($this->post->forum->forumset['allowsell'] && $allowThreadExtend['sell']) ? 1 : 0,
            'price' => $sellCreditRange['maxprice'],
            'income' => $sellCreditRange['maxincome'],
            'credit' => Pw::subArray($creditBo->cType, $this->loginUser->getPermission('sell_credits'))
        );
        !$sellConfig['credit'] && $sellConfig['credit'] = array_slice($creditBo->cType, 0, 1, true);

        $enhideConfig = array(
            'ifopen' => ($this->post->forum->forumset['allowhide'] && $allowThreadExtend['hide']) ? 1 : 0,
            'credit' => Pw::subArray($creditBo->cType, $this->loginUser->getPermission('enhide_credits'))
        );
        !$enhideConfig['credit'] && $enhideConfig['credit'] = array_slice($creditBo->cType, 0, 1, true);

        $allowUpload = ($this->post->user->isExists() && $this->post->forum->allowUpload($this->post->user) && ($this->post->user->getPermission('allow_upload') || $this->post->forum->foruminfo['allow_upload'])) ? 1 : 0;
        $attachnum = intval(Wekit::C('attachment', 'attachnum'));
        if ($perday = $this->post->user->getPermission('uploads_perday')) {
            $count = $this->post->user->info['lastpost'] < Pw::getTdtime() ? 0 : $this->post->user->info['todayupload'];
            $attachnum = max(min($attachnum, $perday - $count), 0);
        }

        $this->setOutput(PwSimpleHook::getInstance('PwEditor_app')->runWithFilters(array()), 'editor_app_config');
        $this->setOutput($this->post, 'pwpost');
        $this->setOutput($this->post->getDisabled(), 'needcheck');
        $this->setOutput($this->post->forum->fid, 'fid');
        $this->setOutput($this->post->forum, 'pwforum');
        $this->setOutput($sellConfig, 'sellConfig');
        $this->setOutput($enhideConfig, 'enhideConfig');
        $this->setOutput($allowThreadExtend, 'allowThreadExtend');
        $this->setOutput($allowUpload, 'allowUpload');
        $this->setOutput($attachnum, 'attachnum');
    }

    private function _bulidAttachs($attach) {
        if (!$attach)
            return '';
        $array = array();
        ksort($attach);
        reset($attach);
        foreach ($attach as $key => $value) {
            $array[$key] = array(
                'name' => $value['name'],
                'size' => $value['size'],
                'path' => Pw::getPath($value['path'], $value['ifthumb'] & 1),
                'thumbpath' => Pw::getPath($value['path'], $value['ifthumb']),
                'desc' => $value['descrip'],
                'special' => $value['special'],
                'cost' => $value['cost'],
                'ctype' => $value['ctype']
            );
        }
        return $array;
    }

    private function _initTopictypes($defaultTopicType = 0) {
        $topictypes = $jsonArray = array();
        $forceTopicType = $this->post->forum->forumset['force_topic_type'];
        if ($this->post->forum->forumset['topic_type']) {
            $permission = $this->loginUser->getPermission('operate_thread', false, array());
            $topictypes = $this->_getTopictypeDs()->getTopicTypesByFid($this->post->forum->fid, !$permission['type']);
            foreach ($topictypes['sub_topic_types'] as $key => $value) {
                if (!is_array($value))
                    continue;
// 				if (!$forceTopicType && $value) $jsonArray[$key][$key] = '无分类';
                foreach ($value as $k => $v) {
                    $jsonArray[$key][$k] = strip_tags($v['name']);
                }
            }
        }
        if ($defaultTopicType && isset($topictypes['all_types'][$defaultTopicType])) {
            $defaultParentTopicType = $topictypes['all_types'][$defaultTopicType]['parentid'];
        } else {
            $defaultTopicType = $defaultParentTopicType = 0;
        }
        $json = Pw::jsonEncode($jsonArray);
        $this->setOutput($defaultTopicType, 'defaultTopicType');
        $this->setOutput($defaultParentTopicType, 'defaultParentTopicType');
        $this->setOutput($topictypes, 'topictypes');
        $this->setOutput($json, 'subTopicTypesJson');
        $this->setOutput($forceTopicType ? 1 : 0, 'forceTopic');
        $this->setOutput('1', 'isTopic');
    }

    /**
     * 
     * Enter description here ...
     * @return PwTopicType
     */
    private function _getTopictypeDs() {
        return Wekit::load('forum.PwTopicType');
    }

}
