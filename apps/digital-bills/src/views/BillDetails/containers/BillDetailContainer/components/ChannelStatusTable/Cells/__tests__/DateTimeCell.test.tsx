import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import DateTimeCell from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ChannelStatusTable/Cells/DateTimeCell';

describe('DateTimeCell', () => {
  test('should render the DateTimeCell component', () => {
    const { getByText } = renderWithWrappers(<DateTimeCell timestamp="2024-02-08T18:29:59.000Z" />);
    expect(getByText('08/02/2024')).toBeInTheDocument();
  });

  test('should render the DateTimeCell component with default value', () => {
    const { getByText } = renderWithWrappers(<DateTimeCell timestamp="" />);
    expect(getByText('-')).toBeInTheDocument();
  });
});
