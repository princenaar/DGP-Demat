const fs = require('node:fs');
const { spawnSync } = require('node:child_process');
const { appEnv, databasePath, root } = require('./env.cjs');

module.exports = async () => {
    fs.closeSync(fs.openSync(databasePath, 'a'));

    const clearConfig = spawnSync('php', ['artisan', 'config:clear', '--no-interaction'], {
        cwd: root,
        env: appEnv,
        stdio: 'inherit',
    });

    if (clearConfig.status !== 0) {
        throw new Error('Unable to clear Laravel configuration before Playwright tests.');
    }

    const result = spawnSync('php', ['artisan', 'migrate:fresh', '--seed', '--no-interaction', '--force'], {
        cwd: root,
        env: appEnv,
        stdio: 'inherit',
    });

    if (result.status !== 0) {
        throw new Error('Unable to prepare the Playwright database.');
    }
};
