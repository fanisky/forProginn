var CryptoJS = require("crypto-js");

var IV = '';
var KEY = '';

/**
 * 加密
 */
function encrypt(str, _key, _vi) {
    var key = CryptoJS.enc.Utf8.parse(_key);// 秘钥
    var iv= CryptoJS.enc.Utf8.parse(_vi);//向量iv
    var encrypted = CryptoJS.AES.encrypt(str, key, { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7});
    return encrypted.toString();
}
/**
 * 解密
 * @param str
 */
function decrypt(str, _key, _vi) {
    var key = CryptoJS.enc.Utf8.parse(_key);// 秘钥
    var iv=    CryptoJS.enc.Utf8.parse(_vi);//向量iv
    var decrypted = CryptoJS.AES.decrypt(str,key,{iv:iv,padding:CryptoJS.pad.Pkcs7});
    console.debug(decrypted.toString());
    return decrypted.toString(CryptoJS.enc.Utf8);
}

//模块测试程序
var decode = decrypt("f5HwcoltPApLEIgGusV6R8FnYo4QLCqOmVFe8qi9RLr5F5qGNwgnJqIqquxkEOp/pa9maVYoPh+DbM2GJkAa+gKvJUnAoWQwAe1sXpLkHQX1vH+K6DkD3S0R3ozNM0GteTA7AyCcwQqB1b+VetsGTiShhO+4m6V5E9Z3KPHQl161vNK2uZhx3l68N+DH5cOy1x3TgpEigJVfHE+eJByVfsriECyRSrqqsEp8h8CihGs=", KEY, IV);
console.log('decode is =======>'+decode);