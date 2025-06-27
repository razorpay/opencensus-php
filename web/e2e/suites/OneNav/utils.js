import { expect } from '@libs/shared-qsuite/playwright';

export async function tabOpenInSameTab({ page, expectedUrl, expectedTextRegex }) {
  await expect(page).toHaveURL(expectedUrl);
  const bodyLocator = page.locator('body');
  await expect(bodyLocator).toHaveText(new RegExp(expectedTextRegex, 'i'));
}

export async function tabOpenInNewTab({
  page,
  buttonText,
  expectedUrl,
  expectedTitleRegex,
  expectedTextRegex,
}) {
  const [newTab] = await Promise.all([
    page.context().waitForEvent('page'),
    page.locator(`button:has-text("${buttonText}")`).click(),
  ]);

  await newTab.waitForLoadState('domcontentloaded');
  // Escape special characters so URL can be matched literally in RegExp
  const escapedUrl = expectedUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  await expect(newTab).toHaveURL(new RegExp(escapedUrl, 'i'));

  const title = await newTab.title();
  await expect(title).toMatch(new RegExp(expectedTitleRegex, 'i'));

  const pageText = await newTab.locator('body').textContent();
  await expect(pageText).toMatch(new RegExp(expectedTextRegex, 'i'));
}

export async function closeWelcomeModal(page) {
  await page.waitForSelector('.welcome-modal-content', { state: 'visible' });
  const welcomeModal = await page.locator('.welcome-modal-content');
  const isWelcomeModalVisible = await welcomeModal.isVisible();
  if (isWelcomeModalVisible) {
    await page.locator('div.Modal-mask span.Modal-close').click();
  }
}

export async function clickMore(page) {
  await waitForOneDashboard(page);
  await page.locator('div[data-blade-component="top-nav-content"] button:has-text("More")').click();
}

export async function scrollAndVerify(page, selector) {
  const container = await page.locator(selector);
  const initialY = await container.evaluate((el) => el.getBoundingClientRect().y);
  await container.evaluate((el) => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
  await page.waitForTimeout(6000);
  const newY = await container.evaluate((el) => el.getBoundingClientRect().y);
  expect(newY).not.toBe(initialY);
}

export async function waitForOneDashboard(page) {
  await page.locator('.main-content--one-dashboard').waitFor({
    state: 'visible',
    timeout: 60000,
  });
}
