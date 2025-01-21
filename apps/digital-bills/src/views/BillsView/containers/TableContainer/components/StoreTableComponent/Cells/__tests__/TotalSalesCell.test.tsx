import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import TotalSalesCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/TotalSalesCell';

describe('TotalSalesCell', () => {
  test('should render the TotalSalesCell component', () => {
    const { getByText } = renderWithWrappers(<TotalSalesCell totalSales={100} />);
    expect(getByText('100')).toBeInTheDocument();
  });
});
