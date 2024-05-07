import { expect } from '@playwright/test';
import { routes } from 'testConstants';
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
  await page.getByPlaceholder('Search').fill(id);
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page.getByRole('cell', { name: id })).toBeVisible();
};

export const gotoTransactionDetailsPageById = async ({ page, id, listSelector }) => {
  const listPage = page.getByTestId(listSelector);
  await page.waitForSelector('.PlaceholderLoader', { state: 'visible', strict: false });
  await page.waitForSelector('.PlaceholderLoader', { state: 'hidden', strict: false });
  await searchTransactionById({ page: listPage, id });
  await listPage
    .getByTestId(`entity-item-row-${id}`)
    .getByRole('button', { name: 'Details' })
    .click();
  await expect(page).toHaveURL(new RegExp(id));
};

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
    await expect(page.getByRole('cell', { name: column })).toBeVisible();
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
  collapsibleSettlementRetryTimeline
    .getByRole('button', { name: 'View previous retry details' })
    .click();
  expect(collapsibleSettlementRetryTimeline.getByText('Settlement failed').first()).toBeVisible();
};
