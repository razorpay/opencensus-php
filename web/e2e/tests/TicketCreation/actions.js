const SELECTORS = require('./selectors');
const { goToPage } = require('../../actions/common');
const constants = require('../../const');

const DEFAULT_CATEGORY = { 
  category: SELECTORS.CATEGORIES.ACTIVATION, 
  subCategory: SELECTORS.SUB_CATEGORIES.ACTIVATION.ACTIVATION_STATUS
};

async function replyToTicket(message) {
  await page.click('textarea.reply-text');
  await page.fill('textarea.reply-text', message);
}

async function navigateToTicketsScreen() {
  const navigationPromise = page.waitForNavigation({ waitUntil: "domcontentloaded"});
  await goToPage(page, constants.routes.HOME);

  await navigationPromise;
  await page.waitForSelector(SELECTORS.SUPPORT_ICON);
 
  await expect(page).toHaveSelector(SELECTORS.SUPPORT_ICON);

  await page.click(SELECTORS.SUPPORT_ICON);

  await page.waitForSelector(SELECTORS.HAVE_A_QUERY);

  await page.click(SELECTORS.HAVE_A_QUERY);

  await page.waitForSelector(SELECTORS.CONTINUE_WITH_TICKET);
  await page.click(SELECTORS.CONTINUE_WITH_TICKET);
}

async function addSubjectToTicket(message) {
  await page.click(SELECTORS.DESCRIPTION_INPUT);
  await page.fill(SELECTORS.DESCRIPTION_INPUT, message);
}

async function selectCategories(data = DEFAULT_CATEGORY) {
  await page.click(data.category);
  if (data.subCategory) {
    await page.click(data.subCategory);
  }
}

async function closeTicketModal() {
  await page.click(SELECTORS.CLOSE_BUTTON);
}

async function generateScreenShot(name) {
  await page.screenshot({path: name, fullPage:true});
}

module.exports = {
  replyToTicket,
  navigateToTicketsScreen,
  addSubjectToTicket,
  selectCategories,
  closeTicketModal,
  generateScreenShot,
};
