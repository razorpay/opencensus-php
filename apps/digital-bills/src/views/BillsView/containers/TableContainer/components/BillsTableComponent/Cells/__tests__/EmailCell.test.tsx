import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import EmailCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/EmailCell';

describe('EmailCell', () => {
  test('should render the EmailCell component', () => {
    const { getByText } = renderWithWrappers(<EmailCell email="test.email@razorpay.com" />);
    expect(getByText('test.email@razorpay.com')).toBeInTheDocument();
  });

  test('should render the EmailCell component with default value', () => {
    const { getByText } = renderWithWrappers(<EmailCell email="" />);
    expect(getByText('-')).toBeInTheDocument();
  });
});
