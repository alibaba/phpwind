<?php
if(!isset($_GET['action'])){//说明页面
?>
<!doctype html>
<html>
<head>
<title>update phpwind9.0.1 to 9.1</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1 class="logo">logo</h1>
        <div class="icon_update">升级向导</div>
        <div class="version">phpwind 9.0.1 to 9.1</div>
    </div>
    
    <div class="main cc">
        <pre class="pact" readonly="readonly">
        升级程序说明：
        1)	运行环境需求：php版本 >php 5.3.x  Mysql版本>5 
        2)	支持升级版本：9.0.1（20141223版）；
        3)	升级后会增加对移动端的支持、对部分功能进行完善及bug的修复；
        4)	更新内容详见补丁包文件，数据变更内容详见数据库sql文件。
        升级步骤：
        1)	关闭站点，后台管理-全局-站点设置-站点状态设置页面进行设置；
        2)	请提前备份您的站点文件，网站根目录下所有文件；
        3)	请提前备份您网站所有的数据库文件；
        4)	请确保已将最新补丁包文件下载解压，并覆盖至文件根目录；
        5)	禁止变更”根目录\src\applications\native”目录；
        6)	请确保“根目录/conf/Database.php”文件存在且数据库连接配置信息正确；
        7)	将pw_new.sql、up9xto91.php文件拷贝到根目录下；
        8)	运行“http://yourwebsite/up9xto91.php”执行升级。
        风险说明：
        因phpwind为开源程序，升级程序是在基础程序上做的更新优化，对基础程序作过二次开发的，可能部分文件、数据库会出现冲突覆盖，可能会导致程序无法正常运行，强烈建议自行手动升级。




        <!--
        phpwind9.x的环境准备，请确认：
        1、确定系统环境
        PHP版本 	> 5.3.x
        PDO_Mysql 安装扩展
        Mysql版本（client） 	>5.x.x
        附件上传 	>2M
        如果确认如上条件都成立，则可以准备开始升级，升级步骤如下：
        1、将phpwind9.x即Nextwind安装包解压，并将upload目录下的文件上传至安装目录。
        （注意，不能直接覆盖原来8.7的环境。如果是虚拟主机，建议先将原87环境除attachment目录外，移动到backup下，这样即使出现问题后可以通过移动目录恢复87的环境。） 
        2、文件转移： 
        2.1、头像图片转移：将原87目录下attachment/upload文件夹，拷贝到phpwind9.x的attachment目录下;（注意如果在第一步已经完成了attachment合并，则此步可忽略。）
        2.2、表情图片转移：将原87目录下images/post/smile/下的所有目录拷贝到phpwind9.x的res/images/emotion/下;
        2.3、勋章图片转移：将原87目录下images/medal/下的所有目录拷贝到phpwind9.x的res/images/medal/下;
        注：如果下载的phpwind9.x包是含有www目录的，则将attachment包括在内的以上目录移到www目录下的对应目录中，比如res/images/emotion/则为www/res/images/emotion/
        3、将升级包up87to90.php文件上传到网站根目录。（如果下载的nextwind包是含有www目录的，则需要放到www目录下）;
        4、确定以下目录的可写权限：
        <font color="red">
        attachment/
        conf/database.php
        conf/founder.php
        conf/windidconfig.php
        data/
        data/cache/
        data/compile/
        data/design/
        data/log/
        data/tmp/
        html/
        src/extensions/
        themes/
        themes/extres/
        themes/forum/
        themes/portal/
        themes/site/
        themes/space/
        </font>
        5、执行升级程序访问站点的升级程序xxx.com/up87to90.php
        6、填写完整需要的数据库信息，及创始人信息
        7、递交之后会执行一步基本配置信息的转换
        8、转换完基本配置信息之后，会正式进入主数据升级，主数据升级页面是允许多进程升级和一键升级选择的页面，在多进程升级中，您可以一次点开多个没有依赖（每步都有说明各自所需的依赖，如果没有说明则没有）的进程。
        注：如果是分进程执行，请确保每一步都执行到位。
        特别说明：如果原87站点开启了ftp服务，那么在分进程页面中会存在单独的一条“用户头像转移”的步骤，请仔细看该步骤说明，该步骤不被包含到一键升级和分进程中，无论选择多进程升级或是一键升级都需要运行，否则用户头像将采用默认头像。
        9、升级执行完之后将会自动进入nextwind9的首页。
        注：如果需要再次升级，请删除data/setup/setup.lock文件
        -->
        </pre>
    </div>
    
    <div class="bottom tac">
        <a href="up9xto91.php?action=dorun" class="btn">同 意</a>
    </div>
</body>
</html>

<?php
}else if($_GET['action']=="form"){
?>
<!doctype html>
<html>
<head>
<title>update phpwind9.0.1 to 9.1</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
    <div class="wrap">
            <div class="header">
                    <h1 class="logo">logo</h1>
                    <div class="icon_update">升级向导</div>
                    <div class="version">phpwind 9.0.1 to 9.1</div>
            </div>
            <div class="section">
                    <div class="step">
                            <ul>
                                    <li class="current" style="width:40%"><em>1</em>设置升级信息</li>
                                    <li class="" style="width:40%"><em>2</em>完成升级</li>
                            </ul>
                    </div>
                    <form method="post" id="J_up87_form" action="up9xto91.php?action=dorun">
                    <div class="server">
                            <table width="100%" style="table-layout:fixed">
                                    <table width="100%" style="table-layout:fixed">
                                    <tr><td class="td1" colspan="3">9.x数据库信息</td></tr>
                            </table>
                            <table width="100%" style="table-layout:fixed">
                                    <tr>
                                            <td width="100" class="tar">数据库服务器：</td>
                                            <td width="210"><input type="text" id="host" name="host" value="localhost" class="input"></td>
                                            <td><div id="J_up87_tip_host"></div></td>
                                    </tr>
                                    <tr>
                                            <td class="tar">数据库用户名：</td>
                                            <td><input type="text" id="username" name="username" value="root" class="input"></td>
                                            <td><div id="J_up87_tip_username"></div></div></td>
                                    </tr>
                                    <tr>
                                            <td class="tar">数据库密码：</td>
                                            <td><input type="password" id="password" name="password" value="" class="input"></td>
                                            <td><div id="J_up87_tip_password"></div></div></td>
                                    </tr>
                                    <tr>
                                            <td class="tar">数据库名：</td>
                                            <td><input type="text" id="dbname" name="dbname" value="nextwind" class="input"></td>
                                            <td><div id="J_up87_tip_dbname"></div></td>
                                    </tr>
                                    <tr>
                                            <td class="tar">数据库端口：</td>
                                            <td><input type="text" id="port" name="port" value="3306" class="input"></td>
                                            <td><div id="J_up87_tip_port"></div></td>
                                    </tr>
                                    <tr>
                                            <td class="tar">数据库表前缀：</td>
                                            <td><input type="text" id="dbpre" name="dbpre" value="nw_" class="input"></td>
                                            <td><div id="J_up87_tip_dbpre"></div></td>
                                    </tr>
                                    <!--
                                    <tr>
                                            <td class="tar">数据库创建引擎：</td>
                                            <td><input type="radio" name="engine" checked value="1"> InnoDB<input type="radio" name="engine" value="0"> MyISAM</td>
                                            <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                            <td class="tar">创始人帐号：</td>
                                            <td><input type="text" id="f_name" name="f_name" value="admin" class="input"></td>
                                            <td><div id="J_up87_tip_f_name"></div></td>
                                    </tr>
                                    <tr>
                                            <td class="tar">创始人密码：</td>
                                            <td><input type="password" id="f_pass" name="f_pass" value="" class="input"></td>
                                            <td><div id="J_up87_tip_f_pass"></div></td>
                                    </tr>
                                    <tr>
                                            <td class="tar">再输入一遍：</td>
                                            <td><input type="password" id="f_rpass" name="f_rpass" value="" class="input"></td>
                                            <td><div id="J_up87_tip_f_rpass"></div></td>
                                    </tr>
                                    -->
                            </table>
                    </div>
                    
                    <div class="bottom tac">
                        <button type="submit" class="btn btn_submit">下一步</button>
                    </div>
            </form>
            </div>
    </div>
    <div class="footer">
            &copy; 2003-2103 <a href="http://www.phpwind.com" target="_blank">phpwind.com</a>（阿里巴巴集团旗下品牌）
    </div>
<script src="res/js/dev/jquery.js"></script>
<script src="res/js/dev/util_libs/validate.js"></script>
<script>
$(function(){
	var focus_tips={'host':'数据库服务器地址，一般为localhost','port':'建议使用默认','dbpre':'建议使用默认，同一数据库安装多个phpwind时需修改'};
    	var form=$("#J_up87_form");
        form.validate({
            errorPlacement:function(error,element){
                                $('#J_up87_tip_'+element[0].name).html(error)
                            },
            errorElement:'div',
            errorClass:'tips_error',
            validClass:'',
            onkeyup:false,
            focusInvalid:false,
            highlight:false,
            rules:{
                        host:{required:true},
                        username:{required:true},
                        dbname:{required:true},
                        port:{required:true},
                        dbpre:{required:true},
                        f_name:{required:true},
                        f_pass:{required:true},
                        f_rpass:{required:true,
                        equalTo:'#f_pass'
                    },
            src_host:{required:true},
            src_username:{required:true},
            src_dbname:{required:true},
            src_port:{required:true},
            src_dbpre:{required:true}},
            unhighlight:function(element,errorClass,validClass){var tip_elem=$('#J_up87_tip_'+element.name);if(element.value){tip_elem.html('<span class="'+validClass+'"><span>')}},
            onfocusin:function(element){var id=element.name,tips=focus_tips[id]?focus_tips[id]:'';$('#J_up87_tip_'+id).html('<span class="gray" data-text="text">'+tips+'</span>')},
            onfocusout:function(element){this.element(element)},
            messages:{
                        host:{required:'Nextwind数据库服务器不能为空'},
                        username:{required:'Nextwind数据库用户名不能为空'},
                        dbname:{required:'Nextwind数据库服务器端口不能为空'},
                        port:{required:'Nextwind数据库服务器端口不能为空'},
                        dbpre:{required:'Nextwind数据库表前缀不能为空'},
                        f_name:{required:'创始人帐号不能为空'},
                        f_pass:{required:'创始人密码不能为空'},
                        f_rpass:{required:'确认密码不能为空',equalTo:'两次输入的密码不一致。请重新输入'},
                        src_host:{required:'PW8.7数据库服务器不能为空'},
                        src_username:{required:'PW8.7数据库用户名不能为空'},
                        src_dbname:{required:'PW8.7数据库名不能为空'},
                        src_port:{required:'PW8.7数据库服务器端口不能为空'},
                        src_dbpre:{required:'PW8.7数据库表前缀不能为空'},
                    }
        })
});
</script>
</body>
</html>

<?php
}else if($_GET['action']=="dorun"){//用户提交基本信息，执行升级过程
    ignore_user_abort(true);
    set_time_limit(0);
    /*
    print str_pad("", 10000);
    @ob_flush();
    flush();
    sleep(5);
     */
    //检查lock文件是否存在
    if (file_exists("./data/up9xto91.lock")) {
	showError('升级程序已被锁定, 如需重新运行，请先删除./data/up9xto91.lock');
    }
    //判断native目录是否存在
    if(!is_dir("./src/applications/native")){
        showError("./src/applications/native 目录不存在,请确保升级包中文件正确覆盖，并且不要修改目录名字");
    }
        
    //执行数据库升级
    $sql_source = file_get_contents("./pw_new.sql");
//    var_dump($sql_source);
    $err_msg = '';
    $db_conf = include_once './conf/database.php';
    $username = $db_conf['user'];
    $password = $db_conf['pwd'];
    $charset = $db_conf['charset'];
    $dbpre = $db_conf['tableprefix'];
    $engine = $db_conf['engine'];
    $dsn = explode(";", $db_conf['dsn']);
//    var_dump($dsn);
    $host = trim(substr($dsn[0],strpos($dsn[0], "=")+1));
    $dbname = trim(substr($dsn[1],strpos($dsn[1], "=")+1));
    $port = trim(substr($dsn[2],strpos($dsn[2], "=")+1));
//    var_dump($host,$dbname,$port);exit;

      
    $sql_source = str_replace(array("{pre}","{charset}","{time}"),array($dbpre,$charset,time()), $sql_source);
//    var_dump($sql_source);
//    $con = mysql_connect($host.":".$port,$username,$password) or die('Could not connect: ' . mysql_error());
    $con = mysql_connect($host.":".$port,$username,$password) or showError('Could not connect: ' . mysql_error());
    mysql_select_db($dbname, $con) or showError('Can\'t use foo : ' . mysql_error());
    $result = mysql_query("SET character_set_connection= 'utf8', character_set_results= 'utf8', character_set_client=BINARY, sql_mode=''") or die("Invalid query: " . mysql_error());
    $result = mysql_query("SET NAMES 'utf8'") or die("Invalid query: " . mysql_error());
    $sql_source = explode(';', $sql_source);
    foreach($sql_source as $k => $v){
        $sql_source[$k] = trim($v);
        if(!$sql_source[$k])unset($sql_source[$k]);
    }

    foreach($sql_source as $sql){
        //执行sql
        $result = mysql_query($sql) or die("Invalid query: " . mysql_error());
    }

    mysql_close($con);
    //热帖权重计算
   $http_host = "http://".((isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:''))."/index.php?m=cron";
   $content = file_get_contents($http_host);
    //生成lock文件
    file_put_contents("./data/up9xto91.lock", "pw9.1");
    $success_text = "恭喜！您的站点已成功升级至phpwind 9.1版本！
                    感谢您使用phpwind，在使用或者升级过程中有任何问题，请反馈至phpwind官方论坛<a href='http://www.phpwind.net' target='_blank'>（http://www.phpwind.net）</a> 
                    ";
    showMsg($success_text);
}


/*错误信息页面*/
function showError($msg, $url = false) {
	global $action,$token;
	if (!$url) {
		if ($action) {
			$url = '<a href="' . $_SERVER['SCRIPT_NAME']. '">返回重新开始</a>';
		} else {
			$url = '<a href="javascript:window.history.go(-1);">返回重新开始</a>';
		}
	} else {
		$url = '';
	}
	echo <<<EOT
<!doctype html>
<html>
<head>
<title>phpwind 9.0.1 to 9.1 升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">phpwind 9.0.1 to 9.1</div>
		</div>

		<div class="success_tip cc error_tip">
			<div class="mb10 f14">$msg</div>
			<div class="error_return">{$url}</div>
		</div>
	</div>
	<div class="footer">
		&copy; 2003-2103 <a href="http://www.phpwind.com" target="_blank">phpwind.com</a>（阿里巴巴集团旗下品牌）
	</div>
</body>
</html>
EOT;
	exit;
}


/*信息页面*/
function showMsg($msg) {
	global $action,$token;
	
	echo <<<EOT
<!doctype html>
<html>
<head>
<title>phpwind 9.0.1 to 9.1 升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">phpwind 9.0.1 to 9.1</div>
		</div>

		<div class="success_tip cc error_tip">
			<div class="mb10 f14">$msg</div>
		</div>
	</div>
	<div class="footer">
		&copy; 2003-2103 <a href="http://www.phpwind.com" target="_blank">phpwind.com</a>（阿里巴巴集团旗下品牌）
	</div>
</body>
</html>
EOT;
	exit;
}

?>

