require('dotenv').config({ path: './e2e/.env' });

module.exports = {
  verbose: true,
  preset: 'jest-playwright-preset',
  roots: ['e2e/tests/'],
  setupFilesAfterEnv: ['expect-playwright', './e2e/jest.setup.js'],
  transform: {},
  testEnvironment: './e2e/CustomEnvironment.js',
  testSequencer: './e2e/CustomSequencer.js',
  testRunner: 'jest-circus/runner',
  globalSetup: './e2e/global.setup.js',
  globalTeardown: './e2e/global.teardown.js',
};
