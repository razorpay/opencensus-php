import { expect } from '@playwright/test';
import { CTA_SELECTORS, CONTENT_SELECTORS } from 'partnerDashboard/common/constants';
import { waitForSelectorToBeVisible } from 'utils/common';

export const fillInputAndLoadSearchResults = async (
  page,
  filterLocator,
  fillValue,
  listLoadSelector,
) => {
  await page.locator(filterLocator).fill(fillValue);
  await page.locator(CTA_SELECTORS.CLIENTS_LIST.SEARCH_BUTTON).click();
  await waitForSelectorToBeVisible({
    page,
    selector: listLoadSelector,
  });
};

export const clickSubmerchantDetailsAndValidate = async (
  page,
  accountId,
  accountName,
  isNameLink,
) => {
  await page.click(`a:has-text("${isNameLink ? accountName : accountId}")`);
  await waitForSelectorToBeVisible({
    page,
    selector: `.ModalSlider__Content :text-is("${accountName}")`,
  });

  await expect(page.locator('.ModalSlider__Content :text-is("Account ID")')).toBeVisible();
  await expect(page.locator(`.ModalSlider__Content :text-is("${accountId}")`)).toBeVisible();
};

export const clickAndLoadAcceptedInvites = async (
  page,
  ctaSelector = CTA_SELECTORS.CLIENTS_LIST.ACCEPTED_INVITES,
) => {
  await page.locator(ctaSelector).click();
  await waitForSelectorToBeVisible({
    page,
    selector: CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
  });
};

export const clickAndLoadAllInvites = async (page) => {
  await page.locator(CTA_SELECTORS.CLIENTS_LIST.ALL_INVITES).click();
  await waitForSelectorToBeVisible({
    page,
    selector: CONTENT_SELECTORS.CLIENTS_LIST.ALL_INVITES.LAST_INVITED_ON,
  });
};
