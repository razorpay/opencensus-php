import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import EntityAnalytics from 'apps/self-serve/src/App/Transactions/v2/Analytics/EntityAnalytics';

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Analytics/EntityAnalytics/FailedPayments.tsx',
  () => ({
    __esModule: true,
    default: () => <div>Failed payments overview</div>,
  }),
);

jest.mock('apps/self-serve/src/App/Transactions/v2/Analytics/EntityAnalytics/Refunds.tsx', () => ({
  __esModule: true,
  default: () => <div>Refunds overview</div>,
}));

export const renderApp = ({ type } = {}) => {
  render(<EntityAnalytics type={type} />);
};
