import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import TransactionCategory from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/TransactionCategory';

describe('TransactionCategory', () => {
  test('should render TransactionCategory component with heading, transactions, and transactionsPercentage', () => {
    const { getByText } = renderWithWrappers(
      <TransactionCategory heading="Category" transactions={1000} transactionsPercent={50} />,
    );
    expect(getByText('Category')).toBeInTheDocument();
    expect(getByText('1K')).toBeInTheDocument();
    expect(getByText('50%')).toBeInTheDocument();
  });
});
