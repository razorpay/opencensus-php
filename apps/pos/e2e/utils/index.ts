export const waitForSalesAssistedScreenToLoad = async ({ page }) => {
  await page.waitForSelector('text=POS Sales Dashboard');
};
