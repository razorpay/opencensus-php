const SELECTOS = require('./selectors');

async function addMember(pageCtx, email) {
  await pageCtx.click(SELECTOS.HEADERS.MANAGE_TEAM);
  await pageCtx.click(SELECTOS.ELEMENTS.INVITE_BUTTON);
  await pageCtx.fill(SELECTOS.ELEMENTS.INVIT_EMAIL_FIELD, email);
  await pageCtx.selectOption(SELECTOS.ELEMENTS.INVITE_SELECT_FIELD, 'manager');
  await pageCtx.click(SELECTOS.ELEMENTS.INVITE_SEND);
}

module.exports = {
  addMember,
};
