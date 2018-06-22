<?php
/**
 * Created by PhpStorm.
 * User: huangzhongyu
 * Date: 2018/6/15
 * Time: 10:17
 */

//设置时区
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

echo "#############################################\n";
echo "####开始检测程序 " . date( 'Y-m-d H:i:s' ) . "\n";

//获取最后一次检测时间
$lastMailDate = @intval( file_get_contents( __DIR__ . '/date.bin' ) );
echo "####上次检测到的邮件时间 " . date( 'Y-m-d H:i:s', $lastMailDate ) . "($lastMailDate)" ."\n";

//获取当前ip列表
$ipList = @json_decode( file_get_contents(__DIR__ . '/ip.bin' ), true );
if( !$ipList ){
    $ipList = [];
}

//创建邮件客户端
$pop3 = new \PHPMailer\PHPMailer\POP3();
$mailbox = new PhpImap\Mailbox($imapPath, $imapUsername, $imapPassword );

$mailsIds = $mailbox->searchMailbox('ALL');
if(!$mailsIds) {
    die("Mailbox is empty");
}

$max = count($mailsIds)-1;
for( $i=$max; $i>=0; $i-- ){

    $id = $mailsIds[$i];
    //获取邮件内容
    $mail = $mailbox->getMail($id);
    echo "####check mail {$id} {$mail->subject}\n";
    if( $mail->subject != $mailTitle ){
        continue;
    }

    $date = strtotime( $mail->date );
    if( $date > $lastMailDate ){

        //发现新邮件
        echo "####new mail#### \n";
        $content = decodeContent( $mail->textPlain );
        if( $content ) {
            $ips = parseContent($content, $date);
            saveIps($ips);
            sendMail( $ips, $date, $content );
            file_put_contents(__DIR__ . '/date.bin', $date);
            break;
        }
    }else{
        //没有新邮件了
        echo "have not new mail date\n";
        break;
    }

}

//解码程序 通过调用外部程序进行解码
function decodeContent( $content )
{
    global $decodeCmd;

    $cmd = sprintf( $decodeCmd, $content );
    $content = `$cmd`;
    return $content;
}

//解析程序 解析解密之后的数据
function parseContent( $content, $time )
{
    global $ipList;
    preg_match_all( "/[\d\.]{7,15}/", $content, $matchs );
//    var_dump( $matchs );
    if( !$matchs ){
        return $ipList;
    }
    foreach( $matchs[0] as $ip ){
        if( !array_key_exists( $ip, $ipList) ){
            echo "####new ip {$ip}\n";
            $ipList[$ip] = $time;
        }else{
            echo "####old ip {$ip} \n";
        }
    }
    return $ipList;
}

//保存数据列表
function saveIps( $ipList )
{
    return file_put_contents( __DIR__ . '/ip.bin', json_encode( $ipList ) );

}

//发送邮件内容给管理员
function sendMail( $ipList, $time, $content = '' )
{
    global $mailto, $SMTPDebug, $mailHost, $mailUsername, $mailPassword, $mailPort, $mailSMTPSecure;

    //格式化邮件内容
    $str = '';
    foreach( $ipList as $ip=>$addTime ){
        $line = $ip . ' ' . date( 'Y-m-d H:i:s', $addTime ) . ( date('Y-m-d',$time) == date( 'Y-m-d',$addTime ) ? '(*)' : '' );
        $str = $line . "\n" . $str;
    }
    echo $str . "\n";

    $subject = "好像发现广东探针更新了 " . date( 'Y-m-d H:i:s' );
    //连接邮件内容
    $message = $str . "\n\n\n" . $content . "\n";

    //创建
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        //Server settings
        $mail->SMTPDebug = $SMTPDebug;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = $mailHost;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = $mailUsername;                 // SMTP username
        $mail->Password = $mailPassword;                           // SMTP password
        if( $mailSMTPSecure != '' ) $mail->SMTPSecure = $mailSMTPSecure;                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $mailPort;                                    // TCP port to connect to
        $mail->CharSet = 'UTF-8';
        //Recipients
        $mail->setFrom($mailUsername, 'Mailer');
        foreach( $mailto as $key=>$item ){
            $mail->addAddress($key, $item);     // Add a recipient
        }

        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        echo 'Message has been sent' . "\n";
    } catch (Exception $e) {
        //异常处理
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        exit('error mail_data 1');
    }
}

