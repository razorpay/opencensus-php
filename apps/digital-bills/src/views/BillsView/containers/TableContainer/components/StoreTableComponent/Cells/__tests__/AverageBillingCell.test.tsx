import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import AverageBillingCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/AverageBillingCell';

describe('AverageBillingCell', () => {
  test('should render the AverageBillingCell component', () => {
    const { getByText } = renderWithWrappers(<AverageBillingCell averageBilling={100} />);
    expect(getByText('100')).toBeInTheDocument();
  });
});
