const net = require('net');
const functions = require( '../lib/functions.js');

const homeDir = __dirname;
// 需要监听的端口
const ports = [21,23,25];
console.info('ports:' + ports);

ports.forEach(port => {
    //创建服务
    tcpServer( port );
});

function tcpServer( port )
{
    // console.debug( port );
    const logHead = '[tcp port:' + port + ']';
    console.info( logHead + 'starting');
    const server = net.createServer((c) => {
        // 'connection' listener
        const host = c.localAddress;
        const port = c.localPort;
        const client_address = c.remoteAddress;
        const client_port = c.remotePort;

        console.log(logHead + 'client connected');
        console.log(logHead + host + '|' + port + '|' + client_address + '|' + client_port);

        //断开连接回调
        c.on('end', () => {
          console.log(logHead + 'client disconnected');
        });

        //连接响应
        c.write('hello\r\n');
    
        //连接数据回调
        c.on('data', function(data){
            console.info(logHead + 'recive:' + data );
            try{
              const logFile = functions.getLogFile( homeDir + '/../', 'server_');
              //console.debug(logFile);
              functions.dataSave(host, port, client_address, client_port, data.toString(), logFile);
          }catch(ex){
            console.info(logHead + 'error:' + ex );
          }

          //处理后断开
          c.end();
        });
      });
    
      //处理异常
      server.on('error', (err) => {
        throw err;
      });
    
      //监听回调
      server.listen(port, '0.0.0.0', () => {
        console.log(logHead + 'server bound ');
      });
}





