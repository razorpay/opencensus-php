import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import TransactionTypeCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/TransactionTypeCell';
import type { TransactionType } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

describe('TransactionTypeCell', () => {
  test.each([
    ['DIGITAL', 'Digital'],
    ['PRINT', 'Print'],
    ['DIGITAL_PRINT', 'Digital + Print'],
  ])('should render the TransactionTypeCell component as expected', (prop, result) => {
    const { getByText } = renderWithWrappers(
      <TransactionTypeCell transactionType={prop as TransactionType} />,
    );
    expect(getByText(result)).toBeInTheDocument();
  });
});
