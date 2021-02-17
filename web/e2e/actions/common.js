async function changeMerchant(pageCtx, merchantName) {
  await pageCtx.click('.switch-merchant');
  await pageCtx.click(`.switch-merchant__Menu >> text="${merchantName}"`);
  await pageCtx.waitForNavigation();
}

async function changeMode(pageCtx, mode) {
  await pageCtx.click('.switch-modes-toggle');
  await pageCtx.click(`.switch-modes-toggle >> ModeIndicator--${mode}`);
}

async function goToPage(pageCtx, page) {
  await pageCtx.goto(page);
}

module.exports = {
  changeMerchant,
  changeMode,
  goToPage,
};
