import React from 'react';
import CreatedOn from 'apps/self-serve/src/App/Transactions/v2/common/components/CreatedOn';
import { render } from 'apps/self-serve/src/services/test/test-utils';

jest.mock('@dashboard/shared-ui/hooks', () => ({
  useMobile: jest.fn(),
}));

export const useMobileMock = jest.requireMock('@dashboard/shared-ui/hooks');

export const renderApp = ({ created_at }) => render(<CreatedOn created_at={created_at} />);
