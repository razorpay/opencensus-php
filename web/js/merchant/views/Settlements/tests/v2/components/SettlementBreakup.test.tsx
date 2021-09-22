import * as SettlementsDB from 'merchant/views/Settlements/tests/data/SettlementsDB';
import React from 'react';
import SettlementBreakup from 'merchant/views/Settlements/v2/components/SettlementBreakup';
import { calculateCreditDebitAmount } from 'merchant/views/Settlements/v2/util';
import { errorHandlers, render, screen, server, waitForLoadingToFinish } from 'test-utils';
import { getFormattedAmount } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';

jest.mock('merchant_common/reducers/notifications', () => ({
  ...(jest.requireActual('merchant_common/reducers/notifications') as Record<string, unknown>),
  __esModule: true,
  default: () => ({ notifications: [] }),
  showNotification: jest.fn().mockReturnValue({ type: 'NOTIFICATION_SHOW', payload: [] }),
}));

test('should load settlements breakup on mount', async () => {
  render(<SettlementBreakup settlementId="settlmenttest" />, {});

  const calculatedAmounts = calculateCreditDebitAmount(
    SettlementsDB.settleBreakupDetails.items,
    true,
  );
  const expectedDebitNodes = SettlementsDB.settleBreakupDetails.items.filter(
    (item) => item.type === 'debit',
  );
  const expectedCreditNodes = SettlementsDB.settleBreakupDetails.items.filter(
    (item) => item.type === 'credit',
  );

  await waitForLoadingToFinish();

  expect(screen.getByText(/Total credit amount/i)).toHaveTextContent(
    new RegExp(`Total credit amount: ₹ ${getFormattedAmount(calculatedAmounts.credit)}`, 'i'),
  );

  expect(screen.getByText(/Total debit amount/i)).toHaveTextContent(
    new RegExp(`Total debit amount: ₹ ${getFormattedAmount(calculatedAmounts.debit * -1)}`, 'i'),
  );

  const debitNodes = screen.getAllByTestId('settlementBreakupdebit');
  expect(debitNodes.length).toBe(expectedDebitNodes.length);

  const creditNodes = screen.getAllByTestId('settlementBreakupcredit');
  expect(creditNodes.length).toBe(expectedCreditNodes.length);
});

test('should show notification on network error', async () => {
  server.use(errorHandlers.internalServerError);

  render(<SettlementBreakup settlementId="settlmenttest" />, {});
  await waitForLoadingToFinish();

  expect(showNotification).toBeCalledTimes(1);
  expect(showNotification).toBeCalledWith({
    type: 'error',
    message: [''],
  });

  expect(screen.queryByText(/Total credit amount/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Total debit amount/i)).not.toBeInTheDocument();
});
