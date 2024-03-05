export const waitForPosCatalogToLoad = async ({ page }) => {
  await page.waitForSelector('text=Device Shop', { timeout: 30000 });
};
