import { expect } from '@playwright/test';
import { routes } from 'testConstants';

import { navigateToInCurlecDashboard } from '../../utils/common';

const { switchToTestMode } = require('../../utils');

export const navigateToSubscriptionsSettings = async (page, mode) => {
  await navigateToInCurlecDashboard(page, routes.DASHBOARD);
  if (mode !== 'live') {
    await switchToTestMode({ page });
  }

  await page.getByRole('link', { name: 'Subscriptions' }).click();
  await expect(page).toHaveURL(routes.SUBSCRIPTIONS);

  const isSettingsVisible = await page.isVisible('text="Settings"');
  if (isSettingsVisible)
    await expect(page.getByRole('link', { name: 'Settings', exact: true })).toBeVisible();

  await navigateToInCurlecDashboard(page, routes.SUBSCRIPTIONS_SETTINGS);
  if (mode !== 'live') {
    await switchToTestMode({ page });
  }
};
