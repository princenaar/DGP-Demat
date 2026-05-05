const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const databasePath = path.join(root, 'database', 'e2e.sqlite');

const appEnv = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'false',
    APP_URL: 'http://127.0.0.1:8010',
    BCRYPT_ROUNDS: '4',
    CACHE_STORE: 'array',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    DEBUGBAR_ENABLED: 'false',
    E2E_TESTING: 'true',
    MAIL_MAILER: 'array',
    QUEUE_CONNECTION: 'sync',
    SESSION_DRIVER: 'file',
};

module.exports = {
    appEnv,
    databasePath,
    root,
};
