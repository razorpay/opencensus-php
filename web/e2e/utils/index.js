const { expect } = require('@playwright/test');
const { formatPhoneNumber } = require('@razorpay/i18nify-js');
const moment = require('moment');

const { COMMON_SELECTORS } = require('./selectors');
const { routes } = require('../constants');

export function getI18FormattedPhoneNumber(contact) {
  try {
    const formattedContact = formatPhoneNumber(contact);
    return formattedContact ? formattedContact : contact;
  } catch (e) {
    return contact;
  }
}

const DEFAULT_DATE_RANGE_IN_DAYS = 30;

export function generateRandomText(length) {
  const characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return result;
}

const generateRandomPhoneNumber = () => {
  return Math.floor(Math.random() * 9000000000) + 1000000000;
};

const generateRandomName = () => {
  return Math.random().toString(36).slice(2, 15);
};

export const generateRandomEmail = () => {
  const phone = generateRandomPhoneNumber();
  const name = generateRandomName();
  return `${name}.${phone}@razorpay.com`;
};

const getDemoGSTIN = () => {
  // TODO: expand this list later on
  const DEMO_GSTINS = ['22AAAAA0000A1Z5'];
  return DEMO_GSTINS[Math.floor(Math.random() * DEMO_GSTINS.length)];
};

export const getRandomCustomerData = () => {
  const phone = generateRandomPhoneNumber();
  const name = generateRandomName();
  const email = generateRandomEmail();
  return {
    name,
    phone: phone.toString(),
    email,
    gstin: getDemoGSTIN(),
  };
};

export const expectSuccessNotification = async ({ page, notificationText }) => {
  await expect(
    await page.locator(COMMON_SELECTORS.successNotification, {
      hasText: notificationText,
    }),
  ).toBeVisible();
};

export const generateRandomWebsiteUrl = () => {
  const keyword = Math.random().toString(36).substring(2, 9);
  return `https://www.youtube.com/${keyword}`;
};

const getDefaultDateRangeForPayments = () => {
  const now = moment();
  const to = now.startOf('D').unix(); // to
  const from = now.startOf('D').subtract(DEFAULT_DATE_RANGE_IN_DAYS, 'days').endOf('D').unix(); // from

  return { to, from };
};

const generateDataForPaymentLink = () => {
  const amount = '100';
  const description = generateRandomText(50).toString();
  const email = generateRandomEmail();
  const phone = generateRandomPhoneNumber().toString();
  const refId = generateRandomText(12);
  return {
    amount,
    description,
    email,
    phone,
    refId,
  };
};

export const switchToTestMode = async ({ page }) => {
  await page.goto(routes.DASHBOARD);
  let modeSwitchToggle;
  // if current mode is already test mode, then no nothing. Else switch to test mode
  // try {
  //   await page.waitForSelector('a.switch-modes-toggle >> text=Test Mode', { timeout: 1000 });
  //   return;
  // } catch (err) {
  //   // continue if error was encountered
  // }
  try {
    modeSwitchToggle = await page.waitForSelector('a.switch-modes-toggle');
  } catch (error) {
    // Element not found within the specified timeout
  }

  if (modeSwitchToggle) {
    await modeSwitchToggle.click();
    await page.locator('li[data-test="Test Mode"]').click();
    await page.waitForSelector('a.switch-modes-toggle');
  }
};

export const hideSearchFTUXBannerByLocalStorage = async ({ page }) => {
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

const hideSearchFTUXBannerByClick = async ({ page }) => {
  let gotItElement;
  try {
    gotItElement = await page.waitForSelector('[data-testid="search-ftux-gotit"]');
  } catch (error) {
    // Element not found within the specified timeout
  }
  if (gotItElement) {
    await gotItElement.click();
  } else {
    console.log('Universal search FTUX "GOT IT" element not found.');
  }
};

const getLastDayOfMonth = (year, month) => {
  return new Date(year, month + 1, 0).getDate();
};

const getNextDate = async ({ page }) => {
  const now = new Date();
  const currentDate = now;

  now.setDate(now.getDate());

  const targetDate = new Date(currentDate.getTime() + 24 * 60 * 60 * 1000);

  let currentMonth = currentDate.getMonth();
  let currentYear = currentDate.getFullYear();

  const targetMonth = targetDate.getMonth();
  const targetYear = targetDate.getFullYear();

  while (currentMonth !== targetMonth || currentYear !== targetYear) {
    // eslint-disable-next-line no-await-in-loop
    await page.getByTitle('Next month (PageDown)').click();
    currentMonth++;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear++;
    }
  }

  const lastDayOfMonth = getLastDayOfMonth(targetYear, targetMonth);
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
  const date = `${monthNames[targetMonth]} ${lastDayOfMonth}, ${targetYear}`;
  return date;
};

export const fillExpiry = async ({ page, isLegacyLink }) => {
  let openDatePicker = true;
  if (isLegacyLink) {
    const checkbox = await page.locator('input[data-name="hasNoExpiry"]');
    if (await checkbox.isChecked()) {
      await page.getByText('No Expiry', { exact: true }).click();
      if (
        await page.waitForSelector('.rc-calendar-table', {
          state: 'visible',
        })
      ) {
        openDatePicker = false;
      }
    }
  }
  if (openDatePicker) {
    await page.getByPlaceholder('DD-MM-YYYY').click();
    let calendarVisible = false;
    while (!calendarVisible) {
      try {
        // eslint-disable-next-line no-await-in-loop
        await page.waitForSelector('.rc-calendar-table', {
          state: 'visible',
        });
        calendarVisible = true;
      } catch (error) {
        // eslint-disable-next-line no-await-in-loop
        await page.getByPlaceholder('DD-MM-YYYY').click();
      }
    }
  }
  const dateToSelect = await getNextDate({
    page,
  });
  await page.locator(`td[title="${dateToSelect}"]`).click();
  await page.waitForSelector('.rc-calendar-table', { state: 'hidden' });
};

module.exports = {
  generateRandomText,
  generateRandomPhoneNumber,
  getI18FormattedPhoneNumber,
  generateRandomName,
  generateRandomEmail,
  getRandomCustomerData,
  getDemoGSTIN,
  expectSuccessNotification,
  generateRandomWebsiteUrl,
  getDefaultDateRangeForPayments,
  generateDataForPaymentLink,
  switchToTestMode,
  hideSearchFTUXBannerByClick,
  hideSearchFTUXBannerByLocalStorage,
  getNextDate,
  fillExpiry,
};
