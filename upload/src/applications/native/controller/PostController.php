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


class PostController extends PwBaseController {
    public $post;
    
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
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
            if (!$this->loginUser->isExists()) {
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
     /index.php?m=native&c=post&a=add&fid=分类id
     //post: atc_title=测试title&atc_content=测试内容&tagnames=测试话题&pid=默认空&tid=默认空&special=default&reply_notice=1
     post: atc_content=#话题#测试内容&pid=默认空&tid=默认空&special=default&reply_notice=1
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */
    public function addAction() {
        
//        echo "addAction";exit;
        //app发帖子不带标题,内容格式化，抓取分享链接内容，此处尚需要处理
        list($title, $content, $topictype, $subtopictype, $reply_notice, $hide, $created_address,$share_url) = $this->getInput(array('atc_title', 'atc_content', 'topictype', 'sub_topictype', 'reply_notice', 'hide' ,'created_address','share_url'), 'post');
        $pwPost = $this->post;
//        var_dump($_POST);exit;
//        $content = "#dfere#aaadsdghj#gdad#sdsd";
//        $_POST['tagnames'] = array('群发话题11','群发话题22','群发话题33');
        preg_match("/^(#[^#]+?#)?(.+)/i",$content, $matches);
        isset($matches[1]) && $_POST['tagnames'] = explode('#', trim($matches[1],'#'));
        isset($matches[2]) && $content = $matches[2];
//        var_dump($_POST['tagnames'],$content);exit;
        $this->runHook('c_post_doadd', $pwPost);
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
        $data = array('tid'=>$tid,'from_type'=>1,'created_address'=>$created_address);
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
     post(回复帖子): tid&atc_content
     post(回复回帖、回复回复->相当于在本楼层回帖): tid&pid&atc_content
     post(在点喜欢的时候顺便回复内容)：tid&pid&atc_content&from_type=like
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */
    public function doreplyAction() {
        $tid = $this->getInput('tid');
        list($title, $content, $hide, $rpid) = $this->getInput(array('atc_title', 'atc_content', 'hide', 'pid'), 'post');
        $_getHtml = $this->getInput('_getHtml', 'get');
        $pwPost = $this->post;
        //runHook的作用？
        $this->runHook('c_post_doreply', $pwPost);

        $info = $pwPost->getInfo();
        $title == 'Re:' . $info['subject'] && $title = '';
        if ($rpid) {
            $post = Wekit::load('thread.PwThread')->getPost($rpid);
            if ($post && $post['tid'] == $tid && $post['ischeck']) {
                $post['content'] = $post['ifshield'] ? '此帖已被屏蔽' : trim(Pw::stripWindCode(preg_replace('/\[quote(=.+?\,\d+)?\].*?\[\/quote\]/is', '', $post['content'])));
                $post['content'] && $content = '[quote=' . $post['created_username'] . ',' . $rpid . ']' . Pw::substrs($post['content'], 120) . '[/quote] ' . $content;
            } else {
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
//        var_dump($pid);exit;
        
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
     * 这个方法暂时没用    
     */
    public function replyAction(){
        $pid = $this->getInput('pid');
        $this->runHook('c_post_reply', $this->post);
        
        $info = $this->post->getInfo();
        $this->setOutput('', 'atc_title');
        $this->setOutput('Re:' . $info['subject'], 'default_title');
        $this->setOutput('doreply', 'do');
        $this->setOutput($info['tid'], 'tid');
        $this->setOutput($pid, 'pid');
        $this->setOutput('checked', 'reply_notice');
        $this->setOutput($this->post->forum->headguide() . $this->post->forum->bulidGuide(array($info['subject'], WindUrlHelper::createUrl('bbs/read/run', array('tid' => $info['tid'], 'fid' => $this->post->forum->fid)))), 'headguide');
        $this->_initVar();
        $this->setTemplate('post_run');
        // seo设置
        Wind::import('SRV:seo.bo.PwSeoBo');
        $seoBo = PwSeoBo::getInstance();
        $lang = Wind::getComponent('i18n');
        $seoBo->setCustomSeo($lang->getMessage('SEO:bbs.post.reply.title'), '', '');
        Wekit::setV('seo', $seoBo);
    }
    
    
     /**
     * 针对某一个楼层的回复列表，带翻页
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=post&a=replylist&tid=33&pid=33&page=2
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
        list($tid, $pid, $page) = $this->getInput(array('tid', 'pid', 'page'), 'get');

        $page = intval($page);
        $page < 1 && $page = 1;
        $perpage = 10;

        $info = Wekit::load('forum.PwThread')->getThread($tid);
        $replydb = array();
        if ($pid) {
            $reply = Wekit::load('forum.PwThread')->getPost($pid);
            $total = $reply['replies'];
            list($start, $limit) = Pw::page2limit($page, $perpage);
            Wind::import('LIB:ubb.PwSimpleUbbCode');
            Wind::import('LIB:ubb.config.PwUbbCodeConvertThread');
            $replydb = Wekit::load('forum.PwPostsReply')->getPostByPid($pid, $limit, $start);
            $replydb = Wekit::load('forum.srv.PwThreadService')->displayReplylist($replydb);
        } else {
            $total = 0;
        }
        
        var_dump(array('page'=>$page,'perpage'=>$perpage,'count'=>$total,'pid'=>$pid,'replydb'=>$replydb,'tid'=>$info['tid']));exit;
        
        $this->setOutput($page, 'page');
        $this->setOutput($perpage, 'perpage');
        $this->setOutput($total, 'count');

        $this->setOutput($pid, 'pid');
        $this->setOutput($replydb, 'replydb');
        $this->setOutput($info['tid'], 'tid');
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
