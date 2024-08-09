/* eslint-disable no-await-in-loop */
import { routes } from 'testConstants';
import { expect } from 'utils/base';
import { navigateTo } from 'utils/common';

const { switchToTestMode } = require('utils');

export const navigateToTransactions = async (page, mode) => {
  await navigateTo(page, routes.DASHBOARD);
  if (mode !== 'live') {
    await switchToTestMode({ page });
  }
  await page.getByRole('link', { name: 'Transactions' }).click();
  await expect(page).toHaveURL(routes.PAYMENTS);
  await expect(page.getByRole('link', { name: 'Payments', exact: true })).toBeVisible();
};

export const searchTransactionById = async ({ page, id }) => {
  let retries = 3;
  await page.getByPlaceholder('Search').fill(id);
  await page.getByRole('button', { name: 'Search' }).click();

  while (retries > 0) {
    try {
      await expect(page.getByRole('cell', { name: id })).toBeVisible({ timeout: 10000 });
      // If the assertion passes, exit the function
      return;
    } catch (error) {
      retries--;
      if (retries > 0) {
        // If the assertion fails and there are retries left, click the refresh button
        await toggleDateFilter({ page });
      }
    }
  }
  throw new Error(`${id}: Transaction not found.`);
};

async function toggleDateFilter({ page }) {
  try {
    const last7DaysDate = page.getByRole('button', { name: 'Last 7 days' });
    await expect(last7DaysDate).toBeVisible({ timeout: 10000 });
    await last7DaysDate.click();
    await page.getByRole('menuitem', { name: 'Today' }).click();
  } catch (err) {
    const todayDate = page.getByTestId('payments-filter').getByRole('button', { name: 'Today' });
    await expect(todayDate).toBeVisible();
    await todayDate.click();
    await page.getByRole('menuitem', { name: 'Last 7 days' }).click();
  }
}

export const waitForListingLoader = async ({ page }) => {
  await page.waitForSelector('.PlaceholderLoader', { state: 'visible', strict: false });
  await page.waitForSelector('.PlaceholderLoader', { state: 'hidden', strict: false });
};

export const gotoTransactionDetailsPageById = async ({ page, id, listSelector }) => {
  const listPage = page.getByTestId(listSelector);
  await searchTransactionById({ page: listPage, id });
  await listPage
    .getByTestId(`entity-item-row-${id}`)
    .getByRole('button', { name: 'Details' })
    .click();
  await expect(page).toHaveURL(new RegExp(id));
  await checkDetailsWithRetries(page);
};

async function checkDetailsWithRetries(page) {
  let retries = 3;

  while (retries > 0) {
    try {
      await expect(page.getByText('Details', { exact: true })).toBeVisible({ timeout: 10000 });
      // If the assertion passes, exit the function
      return;
    } catch (error) {
      retries--;
      if (retries > 0) {
        // If the assertion fails and there are retries left, click the refresh button
        await page.locator('text="Refresh"').click(); // Adjust the selector for the refresh button as needed
      }
    }
  }
  throw new Error(`Details page not loaded.`);
}

export const assertIssueRefundButton = async ({ page, testId }) => {
  await expect(
    page.getByTestId(testId).getByRole('button', { name: 'Issue refund' }),
  ).toBeVisible();
  await page.getByTestId(testId).getByRole('button', { name: 'Issue refund' }).click();
  await expect(page.getByRole('heading', { name: 'Refund Payment' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Issue Full refund' })).toBeVisible();
  await page.getByTestId('modal-header-close-btn').click();
  await expect(page.getByRole('heading', { name: 'Refund Payment' })).not.toBeVisible();
  await expect(
    page.getByText('Initiate full, partial, or instant refunds to your customers'),
  ).toBeVisible();
};

export const assertCollapsibleRefundProcessedTimeline = async ({ page, count = 0 }) => {
  await expect(page.getByRole('heading', { name: 'Timeline' })).toBeVisible();
  const collapsibleRefundsTimeline = page.getByTestId('collapsible-refunds-timeline').nth(count);
  await collapsibleRefundsTimeline.getByRole('button', { name: 'View timeline' }).click();
  await expect(collapsibleRefundsTimeline.getByText('Refund processing')).toBeVisible();
  await expect(collapsibleRefundsTimeline.getByText('Refund processed')).toBeVisible();
};

export const assertRefundDetails = async ({ page, id, amount }) => {
  await expect(page.getByTestId('refund-heading')).toBeVisible();
  const refundDetails = page.getByTestId(`payment-refunded-${id}`);
  await expect(refundDetails.getByText('Refund ID')).toBeVisible();
  await expect(refundDetails.getByText(id)).toBeVisible();
  await expect(refundDetails.getByText('Refund speed')).toBeVisible();
  await expect(refundDetails.getByText('Normal')).toBeVisible();
  await expect(
    page
      .getByTestId(`payment-refunded-${id}`)
      .getByText(`Amount₹ ${amount}₹ - Indian Rupee (INR)`, { exact: true }),
  ).toBeVisible();
  await expect(refundDetails.getByText('Timeline')).toBeVisible();
  await expect(refundDetails.getByText('Refund processing')).toBeVisible();
  await expect(refundDetails.getByText('Refund processed')).toBeVisible();
};

export const assertColumnsVisibility = async (page, columns) => {
  for await (const column of columns) {
    await expect(page.getByRole('cell', { name: new RegExp(column) })).toBeVisible();
  }
};

export const waitForApiResponse = async ({ page, id }) => {
  try {
    await Promise.race([
      page.waitForResponse((response) => response.url().includes('&intl_bank_transfer=1')),
      page.waitForTimeout(2000),
    ]);
  } catch (error) {
    throw new Error(`${id}: Timeout waiting for the API response.`);
  }
};

export const assertSearch = async ({ page, id }) => {
  await page.locator('input[name="id"]').fill(id);
  await page.getByRole('button', { name: 'Search' }).click();
  await waitForApiResponse({ page, id });
};

export const assertPaymentDetails = async ({ page, details }) => {
  for await (const detail of details) {
    await expect(page.getByRole('cell', { name: detail })).toBeVisible();
  }
};

export const assertCollapsibleSettlementRetryTimeline = async ({ page, count = 0 }) => {
  await expect(page.getByRole('heading', { name: 'Timeline' })).toBeVisible();
  const collapsibleSettlementRetryTimeline = page.getByTestId('transaction-timeline').nth(count);
  const viewPreviousRetryDetailsButton = collapsibleSettlementRetryTimeline.getByRole('button', {
    name: 'View previous retry details',
  });
  expect(viewPreviousRetryDetailsButton).toBeVisible();
  await viewPreviousRetryDetailsButton.click();
  expect(collapsibleSettlementRetryTimeline.getByText('Settlement failed').first()).toBeVisible();
};
