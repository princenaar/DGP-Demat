const { spawn } = require('node:child_process');
const { appEnv, root } = require('./env.cjs');

const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8010'], {
    cwd: root,
    env: appEnv,
    stdio: 'inherit',
});

const stopServer = () => {
    if (!server.killed) {
        server.kill();
    }
};

process.on('SIGINT', stopServer);
process.on('SIGTERM', stopServer);
process.on('exit', stopServer);

server.on('exit', (code) => {
    process.exit(code ?? 0);
});
