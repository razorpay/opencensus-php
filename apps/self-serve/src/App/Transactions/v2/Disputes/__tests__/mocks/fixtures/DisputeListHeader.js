import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import DisputeListHeader from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListHeader/DisputeListHeader';

const mockProps = {
  isFetchingTableData: false,
  showNotification: jest.fn(),
  mid: '123',
};

export const renderApp = (props) => render(<DisputeListHeader {...mockProps} {...props} />);
