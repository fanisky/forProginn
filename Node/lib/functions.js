const fs = require('fs');
const CryptoJS = require("crypto-js");

/**
 * 获取日志文件名
 */
function getLogFile( homeDir, tag='' )
{

  const now = new Date();
  const date = now.getFullYear() + '' + p(now.getMonth()+1) + '' + p(now.getDate()) + '' + p(now.getHours()) + '' + p(now.getMinutes());

  //check log file
  log = homeDir + '/logs/' + tag + 'tz_' + date + '.log';
  //console.debug( log );
  
  try{
      fs.accessSync(log,fs.constants.F_OK);
  }catch(ex){
      //console.log('文件不存在:' + log);
      fs.appendFileSync(log, '');
  }

  return log;
}

/**
 * 简单的补0程序
 */
function p(s) {
    return s < 10 ? '0' + s: s;
}

/**
 * 
 * 保存内容到文件，采用json格式保存
 */
function dataSave( host, port, client_address, client_port, data, logFile, protocol = 'tcp')
{
    //此处代码涉及公司数据结构，固删掉了

}

/**
 * 加密
 */
function encrypt(str, _key, _iv) {
    var key = CryptoJS.enc.Utf8.parse(_key);// 秘钥
    var iv= CryptoJS.enc.Utf8.parse(_iv);//向量iv
    var encrypted = CryptoJS.AES.encrypt(str, key, { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7});
    return encrypted.toString();
}
/**
 * 解密
 */
function decrypt(str, _key, _iv) {
    var key = CryptoJS.enc.Utf8.parse(_key);// 秘钥
    var iv=    CryptoJS.enc.Utf8.parse(_iv);//向量iv
    var decrypted = CryptoJS.AES.decrypt(str,key,{iv:iv,padding:CryptoJS.pad.Pkcs7});
    return decrypted.toString(CryptoJS.enc.Utf8);
}


exports.getLogFile = getLogFile;
exports.dataSave = dataSave;
exports.encrypt = encrypt;
exports.decrypt = decrypt;