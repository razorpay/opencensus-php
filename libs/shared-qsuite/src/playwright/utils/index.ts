import { expect, ElementHandle, TestInfo } from '@playwright/test';
import { playwrightEnvs } from '../constants';
// @ts-ignore
import { formatPhoneNumber } from '@razorpay/i18nify-js';
import moment from 'moment';
import { COMMON_SELECTORS } from './selectors';
import { routes } from '../constants';
import { ExtendedPage as Page } from './base';

export const getI18FormattedPhoneNumber = (contact: string): string => {
  try {
    const formattedContact = formatPhoneNumber(contact);
    return formattedContact || contact;
  } catch (e) {
    return contact;
  }
};

const DEFAULT_DATE_RANGE_IN_DAYS = 30;

export const generateRandomText = (length: number): string => {
  const characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return result;
};

export const generateRandomPhoneNumber = (): string => {
  return (Math.floor(Math.random() * 9000000000) + 1000000000).toString();
};

export const generateRandomName = (): string => {
  return Math.random().toString(36).slice(2, 15);
};

export const generateRandomEmail = (): string => {
  const phone = generateRandomPhoneNumber();
  const name = generateRandomName();
  return `${name}.${phone}@razorpay.com`;
};

export const getDemoGSTIN = (): string => {
  const DEMO_GSTINS = ['22AAAAA0000A1Z5'];
  return DEMO_GSTINS[Math.floor(Math.random() * DEMO_GSTINS.length)];
};

export const getRandomCustomerData = (): {
  name: string;
  phone: string;
  email: string;
  gstin: string;
} => {
  return {
    name: generateRandomName(),
    phone: generateRandomPhoneNumber(),
    email: generateRandomEmail(),
    gstin: getDemoGSTIN(),
  };
};

export const getRandomItemData = (): { name: string; amount: string } => {
  return {
    name: generateRandomName(),
    amount: '100',
  };
};

export const expectSuccessNotification = async ({
  page,
  notificationText,
}: {
  page: Page;
  notificationText: string;
}): Promise<void> => {
  await expect(
    page.locator(COMMON_SELECTORS.successNotification, {
      hasText: notificationText,
    }),
  ).toBeVisible();
};

export const generateRandomWebsiteUrl = (): string => {
  const keyword = Math.random().toString(36).substring(2, 9);
  return `https://www.youtube.com/${keyword}`;
};

export const getDefaultDateRangeForPayments = (): { to: number; from: number } => {
  const now = moment();
  const to = now.startOf('D').unix();
  const from = now.startOf('D').subtract(DEFAULT_DATE_RANGE_IN_DAYS, 'days').endOf('D').unix();
  return { to, from };
};

export const generateDataForPaymentLink = (): {
  amount: string;
  description: string;
  email: string;
  phone: string;
  refId: string;
} => {
  return {
    amount: '100',
    description: generateRandomText(50),
    email: generateRandomEmail(),
    phone: generateRandomPhoneNumber(),
    refId: generateRandomText(12),
  };
};

export const switchToTestMode = async ({ page }: { page: Page }): Promise<void> => {
  await page.goto(routes.DASHBOARD);

  let modeSwitchToggle: ElementHandle<SVGElement | HTMLElement> | undefined = undefined;

  try {
    modeSwitchToggle = await page.waitForSelector('a.switch-modes-toggle');
  } catch (error) {
    console.log('Mode switch toggle not found');
  }

  if (modeSwitchToggle) {
    await modeSwitchToggle.click();
    await page.locator('li[data-test="Test Mode"]').click();
    await page.waitForSelector('a.switch-modes-toggle');
  }
};

export const hideSearchFTUXBannerByLocalStorage = async ({
  page,
}: {
  page: Page;
}): Promise<void> => {
  await page.addInitScript(() => {
    window.localStorage.setItem(
      'universal-search-ftux',
      JSON.stringify({
        count: 3,
        expireAt: '2023-05-12T15:25:27+05:30',
      }),
    );
  });
};

export const getNextDate = async ({ page }: { page: Page }): Promise<string> => {
  const now = new Date();
  const targetDate = new Date(now.getTime() + 24 * 60 * 60 * 1000);
  const monthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
  ];
  return `${
    monthNames[targetDate.getMonth()]
  } ${targetDate.getDate()}, ${targetDate.getFullYear()}`;
};

export const fillExpiry = async ({
  page,
  isLegacyLink,
}: {
  page: Page;
  isLegacyLink: boolean;
}): Promise<void> => {
  if (isLegacyLink) {
    const checkbox = page.locator('input[data-name="hasNoExpiry"]');
    if (await checkbox.isChecked()) {
      await page.getByText('No Expiry', { exact: true }).click();
    }
  }
  await page.getByPlaceholder('DD-MM-YYYY').click();
  const dateToSelect = await getNextDate({ page });
  await page.locator(`td[title="${dateToSelect}"]`).click();
  await page.waitForSelector('.rc-calendar-table', { state: 'hidden' });
};

export const clickSkipAndStartBtn = async ({ page }: { page: Page }): Promise<void> => {
  const skipAndStartButton = await page.locator('button:has-text("Skip And Get Started")');
  if (await skipAndStartButton.isVisible()) {
    await skipAndStartButton.click();
    await page.waitForTimeout(5000);
  }
};

export const waitForLoader = async ({
  page,
  selector,
}: {
  page: Page;
  selector: string;
}): Promise<void> => {
  await expect(page.locator(selector)).toBeVisible();
  await expect(page.locator(selector)).not.toBeVisible();
};

// ================================
//  Utils for Success Rate
// ================================
const removeTags = (str: string) => str.replace(/@.*$/i, '');

export const formatDataForSR = ({
  file,
  titlePath,
  status,
}: Pick<TestInfo, 'file' | 'titlePath' | 'status'>) => {
  const formattedTitle = titlePath
    .slice(1)
    .map(removeTags)
    .map((str) => str.trim())
    .join(' | ')
    .toLowerCase()
    .trim();
  const dataPoints = {
    title: formattedTitle,
    status: status === 'passed' ? 'passed' : 'failed',
    module: file.toLowerCase(),
  };
  return dataPoints;
};

export const pushSRData = async ({ testInfo }: { testInfo: TestInfo }) => {
  const isCI = process.env.CI;
  const { E2E_SR_LUMBERJACK_KEY: LJ_KEY } = playwrightEnvs;
  const { file, titlePath, status } = testInfo;

  const metricName = 'merchant.dashboard.e2e.status';
  const srData = formatDataForSR({ file, titlePath, status });
  if (isCI) {
    // push to querybook
    const body = {
      mode: 'live',
      key: LJ_KEY,
      events: [
        {
          event_type: 'pg-dashboard',
          event: metricName,
          event_version: 'v1',
          timestamp: new Date().getTime(),
          properties: {
            ...srData,
          },
        },
      ],
    };

    await fetch('https://lumberjack.razorpay.com/v1/track', {
      method: 'post',
      body: JSON.stringify(body),
      headers: {
        'Content-Type': 'application/json',
      },
      keepalive: true,
    })
      .then(() => {
        console.log('Successfully pushed SR data');
      })
      .catch((e) => {
        console.log('Error in pushing SR data', e);
      });

    // push to grafana
    const myHeaders = new Headers();
    myHeaders.append('Accept', '*/*');
    myHeaders.append('Connection', 'keep-alive');
    myHeaders.append('Content-Type', 'application/json');
    const raw = JSON.stringify({
      key: LJ_KEY,
      metrics: [
        {
          name: metricName,
          labels: [srData],
        },
      ],
    });
    const requestOptions = {
      method: 'POST',
      headers: myHeaders,
      body: raw,
    };
    await fetch(
      'https://lumberjack-metrics.razorpay.com/v1/frontend-metrics',
      requestOptions,
    ).catch(() => {});
  } else {
    console.log('SR Metric', srData);
  }
};

// ================================
//  End of Utils for Success Rate
// ================================

export * from './base';
export * from './common';
export * from './verification';
export * from './selectors';
