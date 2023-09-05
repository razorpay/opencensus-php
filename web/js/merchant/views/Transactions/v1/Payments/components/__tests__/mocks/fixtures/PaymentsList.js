import React from 'react';
import PaymentsList from 'merchant/views/Transactions/v1/Payments/List';
import { render } from 'test-utils';
import 'jest-location-mock';

jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentsTable',
  () =>
    ({ EmptyComponent }) =>
      (
        <div>
          Payments Table
          <EmptyComponent />
        </div>
      ),
);

jest.mock('merchant/views/Transactions/v1/Payments/components/PaymentFailureAnalysis', () => () => (
  <div>Payment Failure Analysis</div>
));

jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentsListFilter',
  () =>
    ({ onSearchAnalytics, onClearAnalytics, onSubmit }) =>
      (
        <div>
          Payments List Filter{' '}
          <button
            type="button"
            onClick={() =>
              onSearchAnalytics({
                key1: 'value1',
              })
            }
          >
            Search Analytics
          </button>
          <button type="button" onClick={() => onClearAnalytics()}>
            Clear Analytics
          </button>
          <button type="button" onClick={() => onSubmit({})}>
            Submit Search
          </button>
        </div>
      ),
);

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);

export const defaultProps = {
  location: {
    search: '?someParam=someValue&',
    pathname: 'somePathname',
    href: 'example.com',
  },
  isRoute: false,
  quickTourFeature: true,
  docUrl: 'docUrl.com',
  failureAnalysisData: {
    data: {},
  },
};

export const defaultStore = {
  session: {
    user: {
      isFAEnabled: true,
      getMaxFAMtv: 2000,
    },
    user_segment_data: {
      average_monthly_transactions: 1000,
    },
  },
  payments: {
    failureAnalysisData: { loading: false, data: {}, error: null },
  },
};

export const renderApp = ({ initialState = defaultStore, props = {} } = {}) => {
  return render(<PaymentsList {...defaultProps} {...props} />, {
    initialState,
  });
};
