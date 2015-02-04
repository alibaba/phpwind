<?php
if(!isset($_GET['action'])){//说明页面
?>
<!doctype html>
<html>
<head>
<title>update phpwind9.0 to nextwind</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1 class="logo">logo</h1>
        <div class="icon_update">升级向导</div>
        <div class="version">phpwind 9.0 to 9.1</div>
    </div>
    
    <div class="main cc">
        <pre class="pact" readonly="readonly">phpwind9.x的环境准备，请确认：
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
<title>update phpwind8.7 to nextwind</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
    <div class="wrap">
            <div class="header">
                    <h1 class="logo">logo</h1>
                    <div class="icon_update">升级向导</div>
                    <div class="version">phpwind 9.0 to 9.1</div>
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
//    var_dump($_GET);
//    var_dump($_POST);
//    exit;
    //判断native目录是否存在
    if(!is_dir("./src/applications/native")){
        showError("目录不存在");
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

    
    /*
    isset($_POST['host']) ? $host = $_POST['host'] : $err_msg .= "host is empty!";
    isset($_POST['username']) ? $username = $_POST['username'] : $err_msg .= "username is empty!";
    isset($_POST['password']) ? $password = $_POST['password'] : $password = "";
    isset($_POST['dbname']) ? $dbname = $_POST['dbname'] : $err_msg .= "dbname is empty!";
    isset($_POST['port']) ? $port = $_POST['port'] : $err_msg .= "port is empty!";
    isset($_POST['dbpre']) ? $dbpre = $_POST['dbpre'] : $err_msg .= "dbpre is empty!";
    if($err_msg) {
//        var_dump($err_msg);exit;
        showError($err_msg);
    }
     * 
     * 
     */
    
    
    $sql_source = str_replace(array("{pre}","{charset}"),array($dbpre,$charset), $sql_source);
//    var_dump($sql_source);
    
//    exit;
    
    
//    $con = mysql_connect($host.":".$port,$username,$password) or die('Could not connect: ' . mysql_error());
    $con = mysql_connect($host.":".$port,$username,$password) or showError('Could not connect: ' . mysql_error());
    mysql_select_db($dbname, $con) or showError('Can\'t use foo : ' . mysql_error());
    $result = mysql_query("SET character_set_connection= 'utf8', character_set_results= 'utf8', character_set_client=BINARY, sql_mode=''") or die("Invalid query: " . mysql_error());
//    var_dump($result);
    $result = mysql_query("SET NAMES 'utf8'") or die("Invalid query: " . mysql_error());
//    var_dump($result);
    $sql_source = explode(';', $sql_source);
    foreach($sql_source as $k => $v){
        $sql_source[$k] = trim($v);
        if(!$sql_source[$k])unset($sql_source[$k]);
    }
//    var_dump($sql_source);

    foreach($sql_source as $sql){
//        var_dump($sql);
        $result = mysql_query($sql) or die("Invalid query: " . mysql_error());
//        var_dump($result);
    }

    mysql_close($con);
//    echo "success";
    showMsg("升级成功！");
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
<title>phpwind 8.7 to 9.x 升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">phpwind 8.7 to 9.x</div>
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


/*错误信息页面*/
function showMsg($msg) {
	global $action,$token;
	
	echo <<<EOT
<!doctype html>
<html>
<head>
<title>phpwind 8.7 to 9.x 升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">phpwind 8.7 to 9.x</div>
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

