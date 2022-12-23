const { chromium } = require('@playwright/test');
const { promises } = require('fs');
const { Login } = require('./Authentication');

async function isFileExists(path) {
  try {
    await promises.access(path);
    return true;
  } catch {
    return false;
  }
}

// Global setup for persisting login among state among pages before going to URL

async function globalSetup(config) {
  const [project] = config.projects;
  const { storageState, baseURL } = project.use;
  const result = await isFileExists(storageState);

  if (result) {
    return;
  }

  const browser = await chromium.launch();
  const page = await browser.newPage();

  const auth = new Login(page, baseURL);
  await auth.login();

  await page.context().storageState({
    path: storageState,
  });

  await browser.close();
}

module.exports = globalSetup;
