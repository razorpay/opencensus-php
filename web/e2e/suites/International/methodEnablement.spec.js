const { test, expect } = require('@playwright/test');
const { BASE_PATH, getStorageStatePath } = require('testConstants');
const { uploadDoc, unStageFiles, assertButton } = require('utils');
const { waitForSelectorToBeVisible, navigateTo } = require('utils/common');

const { DOCS } = require('./constants');

const CONSTANTS = {
  IE_TAB_URL: '/app/payment-methods/international-payments',
  REQUEST_MORE_CTA: 'button[role="button"]:has-text("Request for more methods")',
  REQUEST_CTA: 'button[data-blade-component="button"] >> text="Request"',
  TOOLTIP_TARGET: '[data-testid="tooltip-interactive-wrapper"] button:has-text("Request")',
  METHOD_ENBL_MODAL: 'div[role="dialog"]',
  SELF_ATT_SECTION: 'Self-Attestation',
  DIGITAL_SIGN_SECTION: 'Digital Signature of the Issuing Authority',
  PRE_REQ_TOGGLE: 'button[role="button"]:has-text("Know alternate ways of document verification")',
  NEXT_CTA: 'button[role="button"]:has-text("Next")',
  SUBMIT_FOR_VERIFICATION_CTA: 'button[role="button"]:has-text("Submit for verification")',
  HOW_TO_ESIGN: 'button[role="button"]:has-text("How do I e-sign or self-attest a document?")',
  PRE_REQ_INFO: 'Pre-requisite Information',
  KYC_CHECKBOX:
    'div[data-blade-component="checkbox"] >> text="I hereby confirm that all above documents have been self-attested/digitally signed by issuing authority"',
};

const TIMEOUT = 120 * 1000;

test.describe.parallel(
  'Test international method enablement flow @flow=ie-method-enablement @project=payments',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).INTERNATIONAL_LOGIN_STATE,
    });
    // TODO: enable these tests after multiple auth setup is done
    test.skip('should show request button in disabled state with method enablement tab @priority=normal', async ({
      page,
    }) => {
      // navigate to IE Route
      await navigateTo(page, CONSTANTS.IE_TAB_URL);

      //wait for request more cta to be visible
      await waitForSelectorToBeVisible(
        { page, selector: CONSTANTS.REQUEST_MORE_CTA },
        { timeout: TIMEOUT },
      );

      //request button should be visible
      const button = await page.locator(CONSTANTS.REQUEST_CTA);
      await expect(button.first()).toBeVisible();
      await expect(button.first()).toBeDisabled();
    });

    test.skip('should be able to open method enablement form and use pre-requisites tab @priority=normal', async ({
      page,
    }) => {
      // navigate to IE Route
      await navigateTo(page, CONSTANTS.IE_TAB_URL);

      //wait for request more cta to be visible
      await waitForSelectorToBeVisible(
        { page, selector: CONSTANTS.REQUEST_MORE_CTA },
        { timeout: TIMEOUT },
      );

      //request more methods button click
      await page.locator(CONSTANTS.REQUEST_MORE_CTA).click();

      //modal should be visible
      await expect(page.locator(CONSTANTS.METHOD_ENBL_MODAL)).toBeVisible();

      //only one section should be visible initially
      expect(page.getByText(CONSTANTS.SELF_ATT_SECTION)).toBeVisible();
      expect(page.getByText(CONSTANTS.DIGITAL_SIGN_SECTION, { exact: true })).not.toBeVisible();

      //toggle click
      await page.locator(CONSTANTS.PRE_REQ_TOGGLE).click();

      //Digital dignatory section should be visible now
      await expect(page.getByText(CONSTANTS.DIGITAL_SIGN_SECTION, { exact: true })).toBeVisible();

      //Next button should be visible and enabled
      await assertButton(page, CONSTANTS.NEXT_CTA);
    });

    test.skip('Should be able to navigate to additional docs tab and upload/remove docs', async ({
      page,
    }) => {
      await navigateTo(page, CONSTANTS.IE_TAB_URL);

      //wait for request more cta to be visible
      await waitForSelectorToBeVisible(
        { page, selector: CONSTANTS.REQUEST_MORE_CTA },
        { timeout: TIMEOUT },
      );

      //request more methods button click
      await page.locator(CONSTANTS.REQUEST_MORE_CTA).click();

      //Next button should be visible and enabled
      await assertButton(page, CONSTANTS.NEXT_CTA);
      await page.locator(CONSTANTS.NEXT_CTA).click();

      //submit for verification button should be disabled by default
      await expect(page.locator(CONSTANTS.SUBMIT_FOR_VERIFICATION_CTA)).toBeDisabled();

      //how to esign should take user back to pre-req screen
      await page.locator(CONSTANTS.HOW_TO_ESIGN).nth(0).click();

      expect(page.getByText(CONSTANTS.PRE_REQ_INFO, { exact: true })).toBeVisible();
      //next button click
      await page.locator(CONSTANTS.NEXT_CTA).click();

      //upload all the files
      await uploadDoc(page, DOCS.AOA);
      await uploadDoc(page, DOCS.MOA);
      await uploadDoc(page, DOCS.UBO);

      //checkbox is click
      await page.locator(CONSTANTS.KYC_CHECKBOX).click();

      //submit for verification button should be disabled by default
      await expect(page.locator(CONSTANTS.SUBMIT_FOR_VERIFICATION_CTA)).toBeEnabled();

      //unstage uploaded files
      await unStageFiles(page);
    });
  },
);
