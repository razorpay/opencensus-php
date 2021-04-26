const SELECTORS = require('./selectors');

async function generateAPIKey(pageCtx) {
  await pageCtx.click(SELECTORS.ELEMENTS.GENERATE_TEST_KEY);
  await pageCtx.click(SELECTORS.ELEMENTS.MODAL_OK);
  await pageCtx.click(SELECTORS.ELEMENTS.CONFIRMATION_MODAL_OK);
}

async function reGenerateAPIKey(pageCtx) {
  await pageCtx.click(SELECTORS.ELEMENTS.REGENERATE_TEST_KEY);
  await pageCtx.click(SELECTORS.ELEMENTS.DEACTIVATE_IMMEDIATELY);
  await pageCtx.click(SELECTORS.ELEMENTS.MODAL_OK);
  await pageCtx.click(SELECTORS.ELEMENTS.CONFIRMATION_MODAL_OK);
}

module.exports = {
  generateAPIKey,
  reGenerateAPIKey,
};
