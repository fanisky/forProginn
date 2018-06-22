<?php
/**
 * Created by PhpStorm.
 * User: huangzhongyu
 * Date: 2018/6/15
 * Time: 10:23
 */


$mailHost = ''; //smtp服务器地址
$mailUsername = ''; //邮箱账户
$mailPassword = ''; //邮箱密码
$mailPort = 25; //邮箱端口
$mailSMTPSecure = ''; //tls ssl // Enable TLS encryption, `ssl` also accepted //加密方式

$mailto = [

];

$imapPath = '';
$imapInboxPath = '';
$imapUsername = '';
$imapPassword = '';

$mailTitle = 'mail subject';
$decodeCmd = '/usr/bin/java -jar /path/mydecode.jar %s';

