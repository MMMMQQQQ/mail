<?php
header("content-type:text/html;charset=utf-8");/*编码格式*/
/*关联准备好的函数库和配置文件*/
/*关联配置信息*/
require_once 'config/config.php';
/*关联functions*/
require_once 'functions/common.func.php';
require_once 'functions/mysql.func.php';
/*邮件关联*/
require_once 'swiftmailer-master/lib/swift_required.php';
require_once 'swiftmailer-master/lib/swift_init.php';

$link=connect3();
/*因为之后insert的时候需要一个表的名字*/
$table="51zxw_user";
$act=$_REQUEST['act'];/*判断接受的是login还是register*/
$username=$_REQUEST['username'];
$password=md5($_POST['password']); /*获得表单的内容用$_POST或者$_GET*/
switch($act){
    case 'reg':
        /*插入数据库*/
        /*关闭事务的自动提交*/
        mysqli_autocommit($link,false);
        /*得到当前时间，因为加密是以用户，密码额当前时间为准*/
        $regTime=time();
        /*得到邮箱*/
        $email=$_POST['email'];
        /*生成token*/
        /*加密产生乱码发给邮箱*/
        $token=md5($username.$password.$regTime);
        /*生成token的过期时间*/
        $token_exptime=$regTime+24*3600;/*一天以后过期*/
            $data=compact('username','password','email','regTime','token','token_exptime');
        /*插入数据*/
        $res=insert($link,$data,$table);   //插入数据到数据库
        /*发送邮件，创建一个transport对象*/
        $transport=Swift_SmtpTransport::newInstance('smtp.sina.com',25);
        /*账号名*/
        $transport->setUsername('mmmmqqqq0801@sina.com');/*->是调用transport特有的方法*/
        /*设置密码*/
        $transport->setPassword('ming7318215');
        $transport->setEncryption('tls');
      //  $transport->setEncryption('ssl');
        /*创建一个发送邮件的对象*/
        $mailer=Swift_Mailer::newInstance($transport);
        /*发送邮件信息对象*/
        $message=Swift_Message::newInstance();
        /*发送者*/
        $message->setFrom(array('mmmmqqqq0801@sina.com'));
        /*接收者*/
        $message->setTo($email);

        /*设置主题*/
        $message->setSubject('注册账号激活');
        $activeStr="?act=active&username={$username}&token={$token}";
        $url="http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].$activeStr;
        /*正文内容*/
        //echo $url;
        $urlEncode=urlencode($url);//加密url
        // echo $urlEncode;

        $emailBody=<<<EOF
         欢迎使用{$username}使用长号激活功能，请点击链接激活账号：
         <a href='{$url}' target='_blank'>{$urlEncode}</a><br/>
         （改链接在24小时内有效）如果上面不是链接形式，请将地址复制到您的浏览器（例如IE）的地址再访问。
EOF;
        $message->setBody($emailBody,"text/html",'utf-8');
       // alertMes('注册成功，请激活使用','index.php');
        try{
            $res1=$mailer->send($message);
            //var_dump($res);
            if($res && $res1){
                mysqli_commit($link);
                mysqli_autocommit($link,true);//打开自动事务提交
                alertMes('注册成功，请激活使用','index.php');
            }

        }catch (Swift_ConnectionException $e){
            die ('邮件服务器错误：').$e->getMessage();
        }
        break;
    case 'active':
      //  echo '激活成功';
        $token=$_GET['token'];
        $username=mysqli_real_escape_string($link,$username);
        $query1="select id,token_exptime from {$table} WHERE username='{$username}'";
       // $query=mysqli_query($link,$query1);

        //mysqli的查询语句
       // $user=mysqli_fetch_assoc($query);
        $user=fetchOne($link, $query1);
        if($user){
            //实现激活
            $now=time();
            //判断时间过期没
            $token_exptime=$user['token_exptime'];
            if($now>$token_exptime){
                delete($link,$table,$username);
                alertMes("激活码过期，请重新注册","index.php");
            }else{
                $data=array('status'=>1);
                $res=update($link,$data,$table,$username);
                if($res){
                    alertMes("激活成功","index.php");
                   // echo "激活成功";
                }else{
                    alertMes("激活失败，请重新激活","index.php");
                }
            }
        }else{
            echo "激活失败，没有找到要激活的用户";
            echo "<br/>";
            var_dump($user);
        }
        break;
    case 'login':
        $username=addslashes($username);
        $query="select id,status from {$table} where username='{$username}' and password='{$password}'";
        $row=fetchOne($link,$query);
        if($row){
            if($row['status']==0){
                alertMes("请先到邮箱激活再来登录",'index.php');
            }else{
                echo "登录成功";
            }
            }else{
            alertMes("用户名或密码错误，重新登录",'index.php');
        }
    break;

}