const constants = require('../../const');
const { goToPage } = require('../../actions/common');
// const { merchants } = require('../data/app-level');

beforeAll(async () => {
  // await changeMerchant(page, merchants.manager);
});

describe('User Permission Checks', () => {
  it('Owner should have access to API Keys', async () => {
    await goToPage(page, constants.routes.SETTINGS);
    await expect(page).toHaveSelector(constants.SELECTORS.SETTINGS.HEADERS.API_KEYS);
  });

  it('Owner should have access to Manage Team', async () => {
    await goToPage(page, constants.routes.MY_ACCOUNT);
    await expect(page).toHaveSelector(constants.SELECTORS.MY_ACCOUNT.HEADERS.MANAGE_TEAM);
  });
});
