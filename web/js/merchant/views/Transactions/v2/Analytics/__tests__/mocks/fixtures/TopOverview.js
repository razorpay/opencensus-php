import { render } from 'test-utils';
import TopOverviewContainer from 'merchant/views/Transactions/v2/Analytics/LandingAnalytics/TopOverview';
import * as ModalActions from 'merchant_common/reducers/modals';

export const openModalSpy = jest.spyOn(ModalActions, 'openModal');

jest.mock(
  'merchant/views/Transactions/v2/Analytics/LandingAnalytics/TopOverview/PaymentMethodSplit.tsx',
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
    { label: 'Credit Card', value: 50 },
    { label: 'Debit Card', value: 25 },
  ],
  isMobile: false,
  currency: 'INR',
  shouldShowSrBanner: false,
  successRateData: 99,
};

export const renderApp = (extraProps = {}) => {
  render(<TopOverviewContainer {...props} {...extraProps} />);
};
