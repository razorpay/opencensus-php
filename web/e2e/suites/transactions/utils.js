import { expect } from '@playwright/test';

import { navigateTo } from '../../utils/common';
import { routes } from '../../utils/constants';

const { switchToTestMode } = require('../../utils');

export const navigateToTransactions = async (page) => {
  await navigateTo(page, routes.DASHBOARD);
  await switchToTestMode({ page });
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
  await expect(page.getByRole('heading', { name: 'Refund' })).toBeVisible();
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
