<?php
define('IN_SAESPOT', 1);

include(dirname(__FILE__) . '/config.php');
include(dirname(__FILE__) . '/common.php');

if($options['qq_appid'] && $options['qq_appkey']){
    header("content-Type: text/html; charset=UTF-8");
    echo '现在流行用 <a href="/qqlogin">QQ登录了</a>';
    exit;
}

if($cur_user){
    header('location: /');
    exit;
}else{
    if($options['close_register']){
        header('location: /login');
        exit;
    }
}

$errors = array();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(empty($_SERVER['HTTP_REFERER']) || $_POST['formhash'] != formhash() || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) !== preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])) {
    	exit('403: unknown referer.');
    }
    
    $name = addslashes(strtolower(trim($_POST["name"])));
    $pw = addslashes(trim($_POST["pw"]));
    $pw2 = addslashes(trim($_POST["pw2"]));
    $seccode = intval(trim($_POST["seccode"]));
    if($name && $pw && $pw2 && $seccode){
        if($pw === $pw2){
            if(strlen($name)<21 && strlen($pw)<32){
                //检测字符
                if(preg_match('/^[a-zA-Z0-9\x80-\xff]{4,20}$/i', $name)){
                    if(preg_match('/^[0-9]{4,20}$/', $name)){
                        $errors[] = '名字不能全为数字';
                    }else{
                        error_reporting(0);
                        session_start();
                        if($seccode === intval($_SESSION['code'])){
                            $db_user = $DBS->fetch_one_array("SELECT id FROM yunbbs_users WHERE name='".$name."' LIMIT 1");
                            if(!$db_user){
                                //正常
                            }else{
                                $errors[] = '这名字太火了，已经被抢注了，换一个吧！';
                            }
                        }else{
                            $errors[] = '验证码输入不对';
                        }
                    }
                }else{
                    $errors[] = '名字 太长 或 太短 或 包含非法字符';
                }
            }else{
                $errors[] = '用户名 或 密码 太长了';
            }
        }else{
            $errors[] = '密码、重复密码 输入不一致'; 
        }
    }else{
       $errors[] = '用户名、密码、重复密码、验证码 必填'; 
    }
    ////
    if(!$errors){
        $pwmd5 = md5($pw);
        
        if($options['register_review']){
            $flag = 1;
        }else{
            $flag = 5;
        }
        $DBS->query("INSERT INTO yunbbs_users (id,name,flag,password,regtime) VALUES (null,'$name', $flag, '$pwmd5', $timestamp)");
        $new_uid = $DBS->insert_id();
        if($new_uid == 1){
            $DBS->unbuffered_query("UPDATE yunbbs_users SET flag = '99' WHERE id='1'");
        }
        
        //设置cookie
        $db_ucode = md5($new_uid.$pwmd5.$timestamp.'00');
        $cur_uid = $new_uid;
        setcookie("cur_uid", $cur_uid, $timestamp+ 86400 * 365, '/');
        setcookie("cur_uname", $name, $timestamp+86400 * 365, '/');
        setcookie("cur_ucode", $db_ucode, $timestamp+86400 * 365, '/');
        header('location: /');
        exit;
    }
}

// 页面变量
$title = '注 册';

$pagefile = dirname(__FILE__) . '/templates/default/'.$tpl.'sigin_login.php';

include(dirname(__FILE__) . '/templates/default/'.$tpl.'layout.php');

?>
