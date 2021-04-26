const { goToPage, changeMode } = require('../../actions/common');
const constants = require('../../const');
const { generateAPIKey, reGenerateAPIKey } = require('./actions');
const SELECTORS = require('./selectors');

describe('Handle API keys', () => {
  it('Merchant should be able to generate/regenerate API key', async () => {
    // Need to change to test mode otherwise app asks for KYC verification
    await goToPage(page, constants.routes.APP_KEYS);
    await changeMode(page, 'Test'); // change to test mode

    // Check if it is generate or regenerate view
    let element = await page.waitForSelector(SELECTORS.ELEMENTS.REGENERATE_TEST_KEY);
    let value = await element.innerText();

    if (value === 'Regenerate Test Key') {
      await reGenerateAPIKey(page);
    } else if (value === 'Generate Test Key') {
      await generateAPIKey(page);
    }

    await expect(page).toHaveText('rzp_test_'); // Do a regex check for key
  });
});
