import { FORM_API_URL, DOCS } from './constants';

const { resolve } = require('path');
const { expect } = require('utils/base');

const unStageFile = async (page, fileName) => {
  const closeButton = await page.locator(
    `.Dropzone#Dropzone-${fileName} [data-testid="btn-dropzone-close"]`,
  );
  await expect(closeButton).toBeVisible();
  await closeButton.click();
  await page.waitForResponse(FORM_API_URL);
};

export const unStageFiles = async (page) => {
  await unStageFile(page, DOCS.AOA);
  await unStageFile(page, DOCS.MOA);
  await unStageFile(page, DOCS.UBO);
};

export const uploadDoc = async (page, fileName) => {
  await page.setInputFiles(
    `input[type="file"][id="fileInput-${fileName}"]`,
    resolve(__dirname, `test-${fileName}.jpeg`),
  );
  await page.waitForResponse(FORM_API_URL);
  await expect(page.locator(`.Dropzone-content-desc:has-text("test-${fileName}")`)).toBeVisible();
};

export const assertButton = async (page, locator) => {
  await expect(page.locator(locator)).toBeVisible();
  await expect(page.locator(locator)).toBeEnabled();
};
