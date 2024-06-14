import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import { useMobile } from '@dashboard/shared-ui/hooks';
import { render, screen, fireEvent, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import PaymentDetailsSection from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsSection';
import { happyFlowProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentDetailsSection';

jest.mock('@dashboard/shared-ui/hooks', () => ({
  ...jest.requireActual('@dashboard/shared-ui/hooks'),
  useMobile: jest.fn(),
}));

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/PaymentTransfers',
  () =>
    function PaymentTransfers() {
      return <div>Payment Transfers</div>;
    },
);

describe('Payment Details Section component', () => {
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
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('Order ID')).toBeInTheDocument();
      expect(screen.getByText(`You pay the Razorpay platform fee`)).toBeInTheDocument();
    });

    test('should render App Name', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('App Name')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.applicationDetails.name}`)).toBeInTheDocument();
    });

    test('should render App ID', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('App ID')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.applicationDetails.id}`)).toBeInTheDocument();
    });

    test('should render Customer details', () => {
      render(<App props={happyFlowProps} />);

      expect(screen.getByText('Customer details')).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.paymentDetails.email}`)).toBeInTheDocument();
      expect(screen.getByText(`${happyFlowProps.paymentDetails.contact}`)).toBeInTheDocument();
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

    // test('should render Pos Specific Details', () => {
    //   const globalState = store.getState();
    //   const user = globalState.session.user;
    //   jest.spyOn(user, 'isOmniEnabledMerchant', 'get').mockReturnValue(true);
    //   render(
    //     <App
    //       props={{
    //         ...happyFlowProps,
    //         paymentDetails: {
    //           ...happyFlowProps.paymentDetails,
    //           source_channel: POS_TRANSACTION_CHANNEL,
    //         },
    //       }}
    //     />,
    //   );
    //   expect(screen.getByText('Payment Gateway ID')).toBeInTheDocument();
    //   expect(
    //     screen.getByText(`${happyFlowProps.paymentDetails.gateway_merchant_id}`),
    //   ).toBeInTheDocument();
    //   expect(screen.getByText('Device details')).toBeInTheDocument();
    //   expect(
    //     screen.getByText(`TID: ${happyFlowProps.paymentDetails.gateway_terminal_id}`),
    //   ).toBeInTheDocument();
    // });
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
