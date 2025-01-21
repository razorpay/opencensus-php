import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import StatusCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/StatusCell';

describe('StatusCell', () => {
  test('should render the StatusCell component', () => {
    const { getByText } = renderWithWrappers(
      <StatusCell
        deliveryStatus={{
          email: 'PENDING',
          sms: 'DELIVERED',
          whatsapp: 'FAILED',
        }}
      />,
    );

    expect(getByText('Pending')).toBeInTheDocument();
    expect(getByText('Sent')).toBeInTheDocument();
    expect(getByText('Failed')).toBeInTheDocument();
  });

  test('should render the StatusCell component for all valid status', () => {
    const { getByText, queryByText } = renderWithWrappers(
      <StatusCell
        deliveryStatus={{
          email: 'NOT_ATTEMPTED',
          sms: 'ATTEMPTED',
          whatsapp: 'FAILED',
        }}
      />,
    );

    // If the status is Not Attempted, it should not be rendered
    expect(queryByText('Not Attempted')).not.toBeInTheDocument();
    expect(getByText('Attempted')).toBeInTheDocument();
  });
});
