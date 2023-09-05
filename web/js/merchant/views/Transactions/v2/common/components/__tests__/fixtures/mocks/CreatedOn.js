import React from 'react';
import { render } from 'test-utils';

import CreatedOn from 'merchant/views/Transactions/v2/common/components/CreatedOn';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(),
}));

export const useMobileMock = jest.requireMock('common/hooks/useMobile');

export const renderApp = ({ created_at }) => render(<CreatedOn created_at={created_at} />);
