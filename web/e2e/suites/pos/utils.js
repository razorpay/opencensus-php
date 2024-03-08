const { POS_SELECTOR_TIMEOUT } = require('./constants');

export const waitForPosCatalogToLoad = async ({ page }) => {
  await page.waitForSelector('text=Device Shop', { timeout: POS_SELECTOR_TIMEOUT });
};
