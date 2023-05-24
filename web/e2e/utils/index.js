const { expect } = require('@playwright/test');
const { COMMON_SELECTORS } = require('./selectors');

function generateRandomText(length) {
  const characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return result;
}

function generateRandomPhoneNumber() {
  return Math.floor(Math.random() * 9000000000) + 1000000000;
}

function generateRandomName() {
  return Math.random().toString(36).slice(2, 15);
}

function generateRandomEmail() {
  const phone = generateRandomPhoneNumber();
  const name = generateRandomName();
  return `${name}.${phone}@razorpay.com`;
}

function getDemoGSTIN() {
  // TODO: expand this list later on
  const DEMO_GSTINS = ['22AAAAA0000A1Z5'];
  return DEMO_GSTINS[Math.floor(Math.random() * DEMO_GSTINS.length)];
}

const getRandomCustomerData = () => {
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

const expectSuccessNotification = async ({ page, notificationText }) => {
  await expect(
    await page.locator(COMMON_SELECTORS.successNotification, {
      hasText: notificationText,
    }),
  ).toBeVisible();
};

function generateRandomWebsiteUrl() {
  const keyword = Math.random().toString(36).substring(2, 9);
  return `https://www.youtube.com/${keyword}`;
}

module.exports = {
  generateRandomText,
  generateRandomPhoneNumber,
  generateRandomName,
  generateRandomEmail,
  getRandomCustomerData,
  getDemoGSTIN,
  expectSuccessNotification,
  generateRandomWebsiteUrl,
};
