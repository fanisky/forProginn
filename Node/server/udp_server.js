var dgram = require('dgram');
const functions = require( '../lib/functions.js');

const host = '0.0.0.0';
const homeDir = __dirname;
const ports = [1001,1002];
console.info('ports:' + ports);

ports.forEach(port => {
    //创建udp服务
    udpServer( port, host );
});

//创建udp服务
function udpServer( port, host )
{
    const logHead = '[udp port:' + port + ']';
    console.info( logHead + 'starting');

    //创建udp服务对象
    var server = dgram.createSocket('udp4');

    //数据回调
    server.on('message', function(msg, rinfo){
        console.log('recv %s(%d bytes) from client %s:%d\n', msg, msg.length, rinfo.address, rinfo.port);

        const client_address = rinfo.address;
        const client_port = rinfo.port;
        const data = msg;

        console.log(logHead + 'client connected');
        console.log(logHead + host + '|' + port + '|' + client_address + '|' + client_port);
        console.info(logHead + 'recive:' + data );

        try{
            const logFile = functions.getLogFile( homeDir + '/../', 'server_');
            functions.dataSave(host, port, client_address, client_port, data.toString(), logFile, 'udp');
        }catch(ex){
            console.info(logHead + 'error:' + ex );
        }
    });

    //异常处理
    server.on('error', function(err){
        throw err;
    });

    //绑定回调
    server.bind(port,host, () => {
        console.log(logHead + 'server bound ');
    });

}