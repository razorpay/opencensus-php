export const waitForPosCatalogToLoad = async ({ page }) => {
  await page.waitForSelector('text=Device Shop');
};

export const waitForPosNcModalToLoad = async ({ page }) => {
  await page.waitForSelector('text=Action Required', { timeout: 30000 });
};
