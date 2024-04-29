import { CTA_SELECTORS } from 'partnerDashboard/common/constants';
import { routes } from 'testConstants';
import { pageConsoleLog, waitForSelectorToBeVisible } from 'utils/common';

const hideInviteFlowFTUXBannerByLocalStorage = async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem(
      'partnerships-invite-flow-tooltip',
      JSON.stringify({
        count: 3,
        expireAt: '2023-05-12T15:25:27+05:30',
      }),
    );
  });
};

export const loadPartnerDashboardHomePage = async (page, partnerRoleType, homePageSelector) => {
  // Hides FTUX popups if any
  await hideInviteFlowFTUXBannerByLocalStorage({ page });

  await pageConsoleLog(page, `Loading partner dashboard for ${partnerRoleType}...`);

  await page.goto(routes.PARTNER_DASHBOARD);
  // Waits for all js-bundles to load
  await waitForSelectorToBeVisible({ page, selector: homePageSelector });
};

export const navigateToClientAccounts = async (
  page,
  partnerRoleType,
  homePageSelector,
  clientAcccountsPageSelector,
) => {
  await loadPartnerDashboardHomePage(page, partnerRoleType, homePageSelector);
  // Load Affiliate Accounts section
  await pageConsoleLog(page, `Loading affiliates section for ${partnerRoleType}...`);
  await page.locator(CTA_SELECTORS.SIDEBAR.PARTNER_NAVLINKS.CLIENT_ACCOUNTS).click();
  await waitForSelectorToBeVisible({
    page,
    selector: clientAcccountsPageSelector,
  });

  await pageConsoleLog(page, `Affiliates Page loaded for ${partnerRoleType}.`);
};

export const loadClientAccountsDirectly = async (
  page,
  partnerRoleType,
  clientAcccountsPageSelector,
) => {
  // Hides FTUX popups if any
  await hideInviteFlowFTUXBannerByLocalStorage({ page });

  await pageConsoleLog(page, `Loading affiliates section directly for ${partnerRoleType}...`);
  await page.goto(routes.CLIENT_ACCOUNTS);
  // Waits for all js-bundles to load
  await waitForSelectorToBeVisible({ page, selector: clientAcccountsPageSelector });
  await pageConsoleLog(page, `Affiliates page loaded for ${partnerRoleType}`);
};
