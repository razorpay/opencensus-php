import React from 'react';
import { render } from 'test-utils';

import DisputeListHeader from 'merchant/views/Transactions/v2/Disputes/components/DisputeListHeader/DisputeListHeader';

const mockProps = {
  isFetchingTableData: false,
  showNotification: jest.fn(),
  mid: '123',
};

export const renderApp = (props) => render(<DisputeListHeader {...mockProps} {...props} />);
