/* udp守护程序 */
const child_process = require('child_process');

function spawn(mainModule) {
    var worker = child_process.spawn('node', [ mainModule ]);

    worker.on('exit', function (code) {
        if (code !== 0) {
            spawn(mainModule);
        }
    });
}

spawn(__dirname + '/udp_server.js');