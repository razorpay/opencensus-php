import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import InvoiceIdCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/InvoiceIdCell';

describe('InvoiceIdCell', () => {
  test('should render the InvoiceIdCell component', () => {
    const { getByRole } = renderWithWrappers(
      <InvoiceIdCell billId="bill_1234" legacyEntityId="123" />,
    );
    expect(getByRole('button', { name: 'bill_1234' })).toBeInTheDocument();
  });
});
