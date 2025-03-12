import { expect, routes } from '@libs/shared-qsuite/playwright';

const UCS_DATA_API_URL = '**/ucs/**/GetComponentData';

export async function getRTUXResponse({ page }) {
  let result;
  try {
    const [response] = await Promise.all([page.waitForResponse(UCS_DATA_API_URL)]);
    const res = await response.json();
    result = res.components;
    if (!result.length) {
      throw new Error('Invalid UCS Data response');
    }
    return result;
  } catch (err) {
    throw new Error('Failure in UCS API');
  }
}

export function getWidgetResponse(components, widgetKey) {
  for (const widget of components) {
    if (widget.type === widgetKey) {
      return widget;
    } else if (widget.type === 'layout') {
      const result = getWidgetResponse(widget.components, widgetKey);
      if (result) {
        return result;
      }
    }
  }
  return undefined;
}

export async function assertAPICallForDataRefresh({ page, title }) {
  const container = await page.getByTestId(`widget-${title}`);
  await expect(container).toBeVisible();
  await container.evaluate((div) => {
    const offsetFromTop = div.getBoundingClientRect().top + window.scrollY - 52;
    window.scrollTo(0, offsetFromTop);
  });

  const dropDown = await container.getByTestId('date-picker-component');
  await expect(dropDown).toBeVisible();

  await dropDown.click();

  const last30daysOption = page.getByRole('option', { name: 'Last 30 days' });
  await Promise.all([page.waitForResponse(UCS_DATA_API_URL), last30daysOption.click()]);
}

export const NAVITEMS = {
  PRIMARY: [
    {
      name: 'Transactions',
      href: routes.TRANSACTIONS_PAYMENTS,
    },
    {
      name: 'Settlements',
      href: routes.SETTLEMENTS,
    },
    {
      name: 'Reconciliation',
      href: `${routes.RECON_DASHBOARD}/processes`,
    },
    {
      name: 'Reports',
      href: routes.REPORTS,
    },
    {
      name: 'Account & Settings',
      href: routes.ACCOUNT_SETTINGS,
    },
  ],
  PAYMENT_PRODUCTS: [
    {
      name: 'Payment Links',
      href: routes.PAYMENT_LINKS,
    },
    {
      name: 'Payment Pages',
      href: routes.PAYMENT_PAGES,
    },
    {
      name: 'Razorpay.me Link',
      href: routes.PAYMENT_HANDLE,
    },
  ],
  EXPANDED_PAYMENT_PRODUCTS: [
    {
      name: 'Invoices',
      href: routes.INVOICES,
    },
    {
      name: 'Subscriptions',
      href: routes.SUBSCRIPTIONS,
    },
    {
      name: 'Smart Collect',
      href: routes.SMART_COLLECT,
    },
  ],
  BANKING_PRODUCTS: [
    {
      name: 'X Banking',
      href: routes.X_BANKING,
    },
  ],
  CONSUMER_PRODUCTS: [
    {
      name: 'Customers',
      href: routes.CUSTOMERS,
    },
    {
      name: 'Offers',
      href: routes.OFFERS_HOME,
    },
    {
      name: 'API Keys and Plugins',
      href: routes.API_KEYS_AND_PLUGINS,
    },
    {
      name: 'Apps & Deals',
      href: routes.APP_STORE,
    },
  ],
};
