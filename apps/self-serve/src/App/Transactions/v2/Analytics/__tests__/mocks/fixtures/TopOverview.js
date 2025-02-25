import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import TopOverviewContainer from 'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics/TopOverview';

export const mockOpenModal = jest.fn();

jest.mock('@federated/apps/shell/commonStore', () => {
  const useStoreMocks = () => mockOpenModal;
  useStoreMocks.setState = jest.fn();
  return {
    ...jest.requireActual('@federated/apps/shell/commonStore'),
    useStore: useStoreMocks,
  };
});

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics/TopOverview/PaymentMethodSplit.tsx',
  () => ({
    __esModule: true,
    default: () => (
      <div>
        <h1>Payment Method Split</h1>
      </div>
    ),
  }),
);

export const props = {
  isPaymentsDataLoading: false,
  isPaymentsDataFailed: false,
  paymentCapturedAmount: 100,
  paymentCapturedCount: 5,
  paymentByMethod: [
    {
      label: 'Credit Card',
      value: 50,
    },
    {
      label: 'Debit Card',
      value: 25,
    },
  ],
  isMobile: false,
  currency: 'INR',
  shouldShowSrBanner: false,
  successRateData: 99,
};

export const renderApp = (extraProps = {}) => {
  render(<TopOverviewContainer {...props} {...extraProps} />);
};
