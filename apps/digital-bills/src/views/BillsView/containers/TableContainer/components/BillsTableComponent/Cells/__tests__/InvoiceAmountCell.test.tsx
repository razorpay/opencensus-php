import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import InvoiceAmountCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/InvoiceAmountCell';

describe('InvoiceAmountCell', () => {
  test('should render the InvoiceAmountCell component', () => {
    const { getByText } = renderWithWrappers(<InvoiceAmountCell amount={100} />);
    expect(getByText('100')).toBeInTheDocument();
  });
});
