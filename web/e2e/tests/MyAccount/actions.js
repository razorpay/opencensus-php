const SELECTORS = require('./selectors');

async function addMember(pageCtx, email) {
  await pageCtx.click(SELECTORS.HEADERS.MANAGE_TEAM);
  await pageCtx.click(SELECTORS.ELEMENTS.INVITE_BUTTON);
  await pageCtx.fill(SELECTORS.ELEMENTS.INVIT_EMAIL_FIELD, email);
  await pageCtx.selectOption(SELECTORS.ELEMENTS.INVITE_SELECT_FIELD, 'manager');
  await pageCtx.click(SELECTORS.ELEMENTS.INVITE_SEND);
}

module.exports = {
  addMember,
};
