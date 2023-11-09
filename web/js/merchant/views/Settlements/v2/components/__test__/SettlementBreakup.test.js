import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';
import React from 'react';
import SettlementBreakup from 'merchant/views/Settlements/v2/components/SettlementBreakup';
import { settlementInfoErrorHandler } from 'merchant/views/Settlements/v2/components/__test__/mocks/handlers';
import {
  render,
  screen,
  server,
  waitForLoadingToFinish,
  checkIfComponentIsEmpty,
} from 'test-utils';
import { getFormattedAmount } from 'common/utils/rzp-utils';

describe('SettlementBreakup', () => {
  const renderApp = (props = {}) => {
    return render(<SettlementBreakup settlementId="test-settlement-id" {...props} />, {
      initialState: {
        settlement: {
          breakupDetails: { loading: true, items: [], error: null, isBreakupNew: null },
        },
      },
    });
  };

  test('should show spinner on mount', () => {
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should load settlements breakup', async () => {
    renderApp();

    const { expectedDebitNodes, expectedCreditNodes } =
      SettlementsDB.settleBreakupDetails.items.reduce(
        (acc, item) => {
          if (item.type === 'debit') {
            acc.expectedDebitNodes.push(item);
          } else if (item.type === 'credit') {
            acc.expectedCreditNodes.push(item);
          }
          return acc;
        },
        { expectedDebitNodes: [], expectedCreditNodes: [] },
      );

    await waitForLoadingToFinish();

    expect(screen.getByText(/Total credit amount/i)).toHaveTextContent(
      new RegExp(`Total credit amount: ₹ ${getFormattedAmount(33948761)}`, 'i'),
    );

    expect(screen.getByText(/Total debit amount/i)).toHaveTextContent(
      new RegExp(`Total debit amount: ₹ ${getFormattedAmount(257350)}`, 'i'),
    );

    const debitNodes = screen.getAllByTestId('settlementBreakupdebit');
    expect(debitNodes.length).toBe(expectedDebitNodes.length);

    const creditNodes = screen.getAllByTestId('settlementBreakupcredit');
    expect(creditNodes.length).toBe(expectedCreditNodes.length);
  });

  test('should show notification on network error', async () => {
    server.use(settlementInfoErrorHandler());

    renderApp();
    await waitForLoadingToFinish();

    checkIfComponentIsEmpty();
    expect(screen.getByText(/Something went wrong/i)).toBeInTheDocument();
    expect(screen.queryByText(/Total credit amount/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Total debit amount/i)).not.toBeInTheDocument();
  });

  test('should show RM when currency passed is MYR', async () => {
    renderApp({ currency: 'MYR' });
    await waitForLoadingToFinish();
    const currencySymbols = screen.getAllByText('RM');
    const currencySymbol = currencySymbols[1];
    expect(currencySymbol).toHaveTextContent('RM');
  });
});
