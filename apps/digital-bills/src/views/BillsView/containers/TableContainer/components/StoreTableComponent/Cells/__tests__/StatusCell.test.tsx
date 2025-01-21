import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import StatusCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/StatusCell';

describe('StatusCell', () => {
  test('should render the StatusCell component for Active store', () => {
    const { getByText } = renderWithWrappers(<StatusCell status={true} />);
    expect(getByText('Active')).toBeInTheDocument();
  });

  test('should render the StatusCell component for Inactive store', () => {
    const { getByText } = renderWithWrappers(<StatusCell status={false} />);
    expect(getByText('Inactive')).toBeInTheDocument();
  });
});
