import React from 'react';
import { formatPhoneNumber } from '@razorpay/i18nify-js/phoneNumber';

import '@testing-library/jest-dom/extend-expect';
import { useMobile } from 'common/hooks/useMobile';
import store from 'merchant/store';
import { POS_TRANSACTION_CHANNEL } from 'merchant/views/Transactions/constants';
import PaymentDetailsSection from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsSection';
import { happyFlowProps } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentDetailsSection';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import { useStore } from 'shell/commonStore';

jest.mock('common/hooks/useMobile', () => ({
  ...jest.requireActual('common/hooks/useMobile'),
  useMobile: jest.fn(),
}));

jest.mock('shell/commonStore', () => ({
  ...jest.requireActual('shell/commonStore'),
  useStore: jest.fn(),
}));

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentTransfers',
  () => () => <div>Payment Transfers</div>,
);

const mockIsConfigTagEnabled = jest.fn();
jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component i18={{ isConfigTagEnabled: jest.fn() }} {...props} />,
  useI18Service: () => ({
    isConfigTagEnabled: mockIsConfigTagEnabled,
  }),
}));

describe('Payment Details Section component', () => {
  beforeAll(() => {
    useStore.mockReturnValue({ session: { user: {} } });
  });

  const App = ({ props }) => {
    return <PaymentDetailsSection {...props} />;
  };

  describe(`Should render different entity statuses(Happy flows)`, () => {
    beforeEach(() => {
      useMobile.mockReturnValue(false);
    });

    test('should render Payment ID', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('Payment ID')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.paymentDetails.id}`)).toBeInTheDocument();
    });

    test('should render Bank RRN', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('Bank RRN')).toBeInTheDocument();
      expect(
        screen.getByText(`${happyFlowProps.paymentDetails.acquirer_data.rrn}`),
      ).toBeInTheDocument();
    });

    test('should render Order Id', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('Order ID')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.paymentDetails.order_id}`)).toBeInTheDocument();
    });

    test('should render Fee bearer', () => {
      render(<App props={happyFlowProps} />, {
        initialState: { session: { user: {}, org: { business_name: 'Razorpay' } } },
      });

      expect(screen.getByText('Order ID')).toBeInTheDocument();
      expect(screen.getByText(`You pay the Razorpay platform fee`)).toBeInTheDocument();
    });

    test('should render App ID and Name', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('App ID')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.applicationDetails.id}`)).toBeInTheDocument();
      expect(screen.getByText('App Name')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.applicationDetails.name}`)).toBeInTheDocument();
    });

    test('should render Customer details', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('Customer details')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.paymentDetails.email}`)).toBeInTheDocument();
      expect(
        screen.getByText(`${formatPhoneNumber(happyFlowProps.paymentDetails.contact)}`),
      ).toBeInTheDocument();
    });

    test('should render Description', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('Description')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.paymentDetails.description}`)).toBeInTheDocument();
    });

    test('should render Payment Transfers', () => {
      render(<App props={happyFlowProps} />);
      expect(screen.getByText('Payment Transfers')).toBeInTheDocument();
    });

    test(`should hide Payment Transfers on "IsConfigTagEnabled return true"`, () => {
      mockIsConfigTagEnabled.mockReturnValue(true);
      render(<App props={happyFlowProps} />);
      expect(screen.queryByText('Transfers')).not.toBeInTheDocument();
      mockIsConfigTagEnabled.mockReset();
    });

    test('should render Payment Transfers', () => {
      render(<App props={happyFlowProps} />);
      expect(screen.getByText('Payment Transfers')).toBeInTheDocument();
    });

    test('should render Pos Specific Details', () => {
      const globalState = store.getState();
      const user = globalState.session.user;
      jest.spyOn(user, 'isOmniEnabledMerchant', 'get').mockReturnValue(true);
      render(
        <App
          props={{
            ...happyFlowProps,
            paymentDetails: {
              ...happyFlowProps.paymentDetails,
              source_channel: POS_TRANSACTION_CHANNEL,
            },
          }}
        />,
      );
      expect(screen.getByText('Payment Gateway ID')).toBeInTheDocument();
      expect(
        screen.getByText(`${happyFlowProps.paymentDetails.gateway_merchant_id}`),
      ).toBeInTheDocument();
      expect(screen.getByText('Device details')).toBeInTheDocument();
      expect(
        screen.getByText(`TID: ${happyFlowProps.paymentDetails.gateway_terminal_id}`),
      ).toBeInTheDocument();
    });
    test('should render Payer name', () => {
      const globalState = store.getState();
      const user = globalState.session.user;
      jest.spyOn(user, 'isPayerNameEnabled', 'get').mockReturnValue(true);
      render(<App props={happyFlowProps} />);
      expect(screen.getByText('Payer Name')).toBeInTheDocument();
      expect(
        screen.getByText(`${happyFlowProps.paymentDetails.upi.payer_name}`),
      ).toBeInTheDocument();
    });
  });

  describe('Mobile view', () => {
    beforeEach(() => {
      useMobile.mockReturnValue({
        matchedDeviceType: 'true',
      });
    });
    test('should render payment section toggle', async () => {
      render(<App props={happyFlowProps} />);

      const iconContainer = screen.getByTestId('collapsible-container');
      expect(iconContainer).toBeInTheDocument();
      expect(screen.getByTestId('chevron-up')).toBeInTheDocument();

      fireEvent.click(iconContainer);
      await waitFor(() => {
        expect(screen.getByTestId('chevron-down')).toBeInTheDocument();
      });
    });
  });
});
