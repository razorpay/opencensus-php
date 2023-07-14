const dotenv = require('dotenv');

// Global setup for persisting state among pages before going to URL
function globalSetup() {
  const TEST_ENV = process.env.TEST_ENV || 'devstack';
  console.log('E2E TEST ENV:', TEST_ENV);
  if (TEST_ENV) {
    dotenv.config({ path: `e2e/env/.env.${TEST_ENV}`, override: true });
  }
}

module.exports = globalSetup;
