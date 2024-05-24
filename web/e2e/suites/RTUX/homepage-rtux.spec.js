/* eslint-disable no-await-in-loop */
import { getStorageStatePath, BASE_PATH, routes } from 'testConstants';
import { expect, test } from 'utils/base';

import { assertAPICallForDataRefresh, getRTUXResponse, getWidgetResponse } from './utils';

test.describe.parallel('RTUX - Transacted Merchant @flow=rtux', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test('should show merchant overview @priority=normal', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    await expect(page.getByTestId('merchant-overview')).toBeVisible();
    const merchantOverviewApiRes = getWidgetResponse(components, 'hero_card');
    const {
      data: { hero_card_data },
    } = merchantOverviewApiRes;
    const { is_settlement } = hero_card_data;
    if (is_settlement) {
      await expect(page.getByRole('heading', { name: 'Current balance' })).toBeVisible();
    } else {
      await expect(
        page.getByRole('heading', { name: /first payment/, exact: false }),
      ).toBeVisible();
    }
  });

  test('should show key updates @priority=normal', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    const keyUpdatesApiRes = getWidgetResponse(components, 'carousel_cards_with_count');
    const { components: keyUpdatesCards, title } = keyUpdatesApiRes;

    await expect(page.getByRole('heading', { name: title })).toBeVisible();
    if (keyUpdatesCards.length) {
      await expect(
        page.getByRole('heading', { name: `(${keyUpdatesCards.length})` }),
      ).toBeVisible();
    } else {
      await expect(page.getByRole('heading', { name: "You're all caught up!" })).toBeVisible();
    }

    for (const { title, description } of keyUpdatesCards) {
      await expect(page.getByText(title).first()).toBeVisible();
      await expect(page.getByText(description).first()).toBeVisible();
    }
  });

  test('should show payments overview', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    const paymentsOverviewApiRes = getWidgetResponse(components, 'tabbed_chart');
    const { components: paymentsOverviewCards, title } = paymentsOverviewApiRes;
    await expect(page.getByRole('heading', { name: title })).toBeVisible();

    for (const { title, id } of paymentsOverviewCards) {
      const tab = page.getByTestId(`tab-${id}`);
      await expect(tab.getByText(title).first()).toBeVisible();
      await tab.click();
    }
    await assertAPICallForDataRefresh({ page, title });
  });

  test('should show top insights charts', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    const topInsightsApiRes = getWidgetResponse(components, 'insight_charts');
    const { components: topInsightsCards, title } = topInsightsApiRes;
    await expect(page.getByRole('heading', { name: title })).toBeVisible();

    for (const { title, id } of topInsightsCards) {
      // ignore asserting dispute card as it's being hidden
      if (id !== '144') {
        await expect(page.getByText(title).first()).toBeVisible();
      }
    }
    await assertAPICallForDataRefresh({ page, title });
  });

  test('should show products recommendation @priority=normal', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    const productRecommendationApiRes = getWidgetResponse(components, 'carousal_card');
    const { components: prodRecommendationCards, title } = productRecommendationApiRes;
    await expect(page.getByRole('heading', { name: title })).toBeVisible();

    for (const { title, description } of prodRecommendationCards) {
      await expect(page.getByRole('heading', { name: title }).first()).toBeVisible();
      await expect(page.getByText(description).first()).toBeVisible();
    }
  });
});

test.describe.parallel('RTUX - Non Transacted Merchant @flow=rtux', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test('should show merchant overview @priority=normal', async ({ page }) => {
    await getRTUXResponse({ page });
    await expect(page.getByTestId('merchant-overview')).toBeVisible();
    await expect(page.getByRole('heading', { name: /first payment/, exact: false })).toBeVisible();
  });

  test('should show key updates @priority=normal', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    const keyUpdatesApiRes = getWidgetResponse(components, 'carousel_cards_with_count');
    const { components: keyUpdatesCards, title } = keyUpdatesApiRes;

    await expect(page.getByRole('heading', { name: title })).toBeVisible();
    if (keyUpdatesCards.length) {
      await expect(
        page.getByRole('heading', { name: `(${keyUpdatesCards.length})` }),
      ).toBeVisible();
    } else {
      await expect(page.getByRole('heading', { name: "You're all caught up!" })).toBeVisible();
    }

    for (const { title, description } of keyUpdatesCards) {
      await expect(page.getByText(title).first()).toBeVisible();
      await expect(page.getByText(description).first()).toBeVisible();
    }
  });
});

test.describe.parallel('RTUX - Header Nav @flow=rtux', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.describe.parallel('RTUX - Non Transacted Merchant @flow=rtux', () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    });

    test('should show announcement nav item @priority=normal', async ({ page }) => {
      await page.goto(routes.DASHBOARD);
      const announcementCTA = await page.getByTestId('header-announcement');
      await expect(announcementCTA).toBeVisible();
      await announcementCTA.click();
      await expect(page.getByText('Announcements')).toBeVisible();
    });

    test('should show status details nav item @priority=normal', async ({ page }) => {
      await page.goto(routes.DASHBOARD);
      const statusDetailsCTA = await page.getByTestId('header-status-details');
      await expect(statusDetailsCTA).toBeVisible();
      await statusDetailsCTA.click();
      await expect(page.getByRole('heading', { name: 'Ecosystem Health' })).toBeVisible();
    });

    test('should show user profile nav item @priority=normal', async ({ page }) => {
      await page.goto(routes.DASHBOARD);
      const userProfileCTA = await page.getByTestId('header-profile-dropdown');
      await expect(userProfileCTA).toBeVisible();
      await userProfileCTA.click();
      await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeVisible();
    });
  });
});
