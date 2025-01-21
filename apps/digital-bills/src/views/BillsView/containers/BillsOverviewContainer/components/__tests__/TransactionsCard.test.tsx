import React from 'react';

import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import TransactionsCard from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/TransactionsCard';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { TOTAL_TRANSACTIONS } from '@apps/digital-bills/src/utils/constants';

describe('TransactionCard', () => {
  test('should renderTransactionCard with amount props', () => {
    const { getByText } = renderWithWrappers(
      <TransactionsCard
        totalTransactions={100000}
        digitalTransactions={10000}
        digitalTransPercent={10}
        printedTransactions={1000}
        printedTransPercent={1}
        digitalPrintedTransactions={100}
        digitalPrintedTransPercent={0.1}
        setSelectedOverviewCategory={jest.fn()}
        expandGraph={jest.fn()}
        isLoading={false}
      />,
    );

    expect(getByText('Total Bills Generated')).toBeInTheDocument();
    expect(getByText('Digital')).toBeInTheDocument();
    expect(getByText('Print')).toBeInTheDocument();
    expect(getByText('Digital + Print')).toBeInTheDocument();
    expect(getByText('100K')).toBeInTheDocument();
    expect(getByText('10K')).toBeInTheDocument();
    expect(getByText('1K')).toBeInTheDocument();
    expect(getByText('100')).toBeInTheDocument();
    expect(getByText('10%')).toBeInTheDocument();
    expect(getByText('1%')).toBeInTheDocument();
    expect(getByText('0.1%')).toBeInTheDocument();
  });

  test('should calls setSelectedOverviewCategory and expandGraph when Total Transactions Card is clicked', async () => {
    const setSelectedOverviewCategory = jest.fn();
    const expandGraph = jest.fn();

    const { getByRole } = renderWithWrappers(
      <TransactionsCard
        totalTransactions={100000}
        digitalTransactions={10000}
        digitalTransPercent={10}
        printedTransactions={1000}
        printedTransPercent={1}
        digitalPrintedTransactions={100}
        digitalPrintedTransPercent={0.1}
        setSelectedOverviewCategory={setSelectedOverviewCategory}
        expandGraph={expandGraph}
        isLoading={false}
      />,
    );
    await userEvent.click(getByRole('button', { name: /Total Transactions Card/i }));
    expect(setSelectedOverviewCategory).toHaveBeenCalledWith(TOTAL_TRANSACTIONS);
    expect(expandGraph).toHaveBeenCalled();
  });
});
