/**
 * udp不能用telnet测试，所以单独建立udp数据包发送程序做测试用
 * 
 */

var dgram = require('dgram');

var clientSocket = dgram.createSocket('udp4');

var messages = "xxxxxxxx";

var index = 0;

function sendMsg(){//send to server
  clientSocket.send(messages, 0, messages.length, 6880, "139.196.205.246");
}

setInterval(sendMsg, 1000);

clientSocket.on('message', function(msg, rinfo){
  console.log('recv %s(%d) from server\n', msg, msg.length);
});

clientSocket.on('error', function(err){
  console.log('error, msg - %s, stack - %s\n', err.message, err.stack);
});

clientSocket.bind(54321);