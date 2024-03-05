const path = require('path');
const dotenv = require('dotenv');
// Global setup for persisting state among pages before going to URL
function globalSetup() {
  const TEST_ENV = process.env.TEST_ENV || 'devstack';
  if (TEST_ENV) {
    dotenv.config({ path: path.resolve(`playwright/env/.env.${TEST_ENV}`) });
  }
}

module.exports = globalSetup;
