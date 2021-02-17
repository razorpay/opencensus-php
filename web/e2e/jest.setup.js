const { jest } = require('@jest/globals');
const constants = require('./const');
const utils = require('./utils');

jest.setTimeout(constants.DEFAULT_TIMEOUT);

if (constants.RETRY_COUNT) {
  jest.retryTimes(constants.RETRY_COUNT);
}

const LOGIN_SELECTORS = constants.SELECTORS.LOGIN;

beforeAll(async () => {
  await page.goto(constants.routes.LOGIN);

  await page.fill(LOGIN_SELECTORS.NORMAL_LOGIN_EMAIL, constants.creds.email);
  await page.fill(LOGIN_SELECTORS.NORMAL_LOGIN_PASSWORD, constants.creds.password);

  await page.click(LOGIN_SELECTORS.NORMAL_LOGIN_SUBMIT);

  await page.waitForNavigation('app/dashboard');

  await page.route('**/*', utils.interceptor);
});
