import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

import { NAVITEMS, assertAPICallForDataRefresh, getRTUXResponse, getWidgetResponse } from './utils';

test.describe.parallel('RTUX - Transacted Merchant @flow=rtux @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().SETTLEMENTS_LOGIN_STATE,
  });

  test.skip('should show merchant overview @priority=normal', async ({ page }) => {
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

  test('should show payments overview @priority=P0', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    const paymentsOverviewApiRes = getWidgetResponse(components, 'tabbed_chart');
    const { components: paymentsOverviewCards, title } = paymentsOverviewApiRes;
    await expect(page.getByRole('heading', { name: title })).toBeVisible();

    for (const { title, id } of paymentsOverviewCards) {
      const tab = page.getByTestId(`tab-${id}`);
      await expect(tab.getByText(title).first()).toBeVisible();
      await tab.click();

      // check if chart is rendered
      expect(page.locator(`[data-testid="chartjs-wrapper-${id}"] canvas`)).toBeVisible();
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

  test('should show products recommendation @priority=P1', async ({ page }) => {
    const components = await getRTUXResponse({ page });

    const productRecommendationApiRes = getWidgetResponse(components, 'carousal_card');
    const { components: prodRecommendationCards, title } = productRecommendationApiRes;
    await expect(page.getByRole('heading', { name: title })).toBeVisible();

    for (const { title, description, actions } of prodRecommendationCards) {
      const cardTitle = page
        .locator(`[data-testid="product-card-widget"] >> text=${title}`)
        .first();
      await expect(cardTitle).toBeVisible();
      await expect(page.getByText(description).first()).toBeVisible();

      const actionCtaProps = actions[0];

      if (actionCtaProps) {
        await cardTitle.hover();
        const actionElement = page.getByRole('link', { name: actionCtaProps.title });
        await expect(actionElement).toBeVisible();

        if (/^http/i.test(actionCtaProps.action)) {
          const popupPromise = page.waitForEvent('popup');
          await actionElement.click();
          const popup = await popupPromise;
          await expect(popup.url()).toContain(actionCtaProps.action);
          await popup.close();
        }
      }
    }
  });
});

test.describe.parallel('RTUX - Non Transacted Merchant @flow=rtux @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().SETTLEMENTS_LOGIN_STATE,
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

test.describe.parallel('RTUX - Header Nav @flow=rtux @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().SETTLEMENTS_LOGIN_STATE,
  });

  test.describe.parallel('RTUX - Non Transacted Merchant @flow=rtux', () => {
    test.use({
      storageState: getStorageStatePath().SETTLEMENTS_LOGIN_STATE,
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
      const userProfileCTA = await page.getByTestId('profile-dropdown');
      await expect(userProfileCTA).toBeVisible();
      await userProfileCTA.click();
      await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeVisible();
    });

    test('should show name of merchant and mid and should copy mid @priority=normal', async ({
      page,
    }) => {
      const userProfileCTA = await page.getByTestId('profile-dropdown');
      await expect(userProfileCTA).toBeVisible();
      await userProfileCTA.click();
      await expect(
        page.getByText('Axis Test', {
          exact: true,
        }),
      ).toBeVisible();
      await expect(
        page.getByText('ABC PVT LTD', {
          exact: true,
        }),
      ).toBeVisible();
      const mid = 'DriCegiYYteBDE';
      await expect(
        page.getByText(mid, {
          exact: true,
        }),
      ).toBeVisible();
      await page.getByLabel('copy merchant id').click();
      const copiedText = await page.evaluate(() => {
        return navigator.clipboard.readText();
      });
      await expect(copiedText).toContain(mid);
    });

    test('should show all sections of side navbar @priority=normal', async ({ page }) => {
      await expect(
        page
          .getByRole('link', { name: 'Home', exact: true })
          .or(page.getByRole('link', { name: 'Selected background Home', exact: true })),
      ).toBeVisible();

      for (const { name, href } of NAVITEMS.PRIMARY) {
        const navItem = await page.getByRole('link', { name, exact: true });
        await expect(navItem).toBeVisible();
        await expect(navItem).toHaveAttribute('href', href);
      }
      await expect(page.getByText('PAYMENT PRODUCTS')).toBeVisible();
      for (const { name, href } of NAVITEMS.PAYMENT_PRODUCTS) {
        const navItem = await page.getByRole('link', { name, exact: true });
        await expect(navItem).toBeVisible();
        await expect(navItem).toHaveAttribute('href', href);
      }

      await expect(page.getByText('BANKING PRODUCTS')).toBeVisible();
      for (const { name, href } of NAVITEMS.BANKING_PRODUCTS) {
        const navItem = await page.getByRole('link', { name, exact: true });
        await expect(navItem).toBeVisible();
        await expect(navItem).toHaveAttribute('href', href);
      }

      for (const { name, href } of NAVITEMS.CONSUMER_PRODUCTS) {
        const navItem = await page.getByRole('link', { name, exact: true });
        await expect(navItem).toBeVisible();
        await expect(navItem).toHaveAttribute('href', href);
      }
    });

    test('show all cta should be visible which will expand list to show other items @priority=normal', async ({
      page,
    }) => {
      const showAllCTA = await page.getByText('Show all', {
        exact: false,
      });
      await expect(showAllCTA).toBeVisible();
      await showAllCTA.click();
      for (const { name, href } of NAVITEMS.EXPANDED_PAYMENT_PRODUCTS) {
        const navItem = await page.getByRole('link', { name, exact: true });
        await expect(navItem).toBeVisible();
        await expect(navItem).toHaveAttribute('href', href);
      }
      const showLessCTA = await page.getByText('Show less', {
        exact: false,
      });
      await expect(showLessCTA).toBeVisible();
      await showLessCTA.click();

      await expect(showAllCTA).toBeVisible();
      await expect(showLessCTA).not.toBeVisible();

      for (const { name } of NAVITEMS.EXPANDED_PAYMENT_PRODUCTS) {
        const navItem = await page.getByRole('link', { name, exact: true });
        await expect(navItem).not.toBeVisible({
          visible: false,
        });
      }
    });

    test('should toggle live and test mode @priority=P0', async ({ page }) => {
      async function getModeDetails(toggler) {
        const current = await toggler.innerText();
        return { current, next: current === 'Live Mode' ? 'Test Mode' : 'Live Mode' };
      }

      async function switchMode() {
        let retries = 3;
        while (retries > 0) {
          try {
            const toggler = await page.locator('a.switch-modes-toggle');
            expect(toggler).toBeVisible();
            const { next } = await getModeDetails(toggler);

            await toggler.click();
            const option = await page.locator(`li[data-test="${next}"]`);
            await expect(option).toBeVisible();
            await option.click();

            const { current } = await getModeDetails(toggler);
            await expect(current).toBe(next);
            break;
          } catch {
            retries -= 1;
            if (retries === 0) {
              throw new Error('Failed to switch mode');
            }
          }
        }
      }

      await switchMode();
      await switchMode();
    });
  });
});
