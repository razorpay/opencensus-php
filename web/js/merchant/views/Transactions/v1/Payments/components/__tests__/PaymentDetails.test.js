import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { useQuery } from '@tanstack/react-query';

import { analyticsTrack } from 'common/utils/analytics';
import User from 'merchant/models/User';
import store from 'merchant/store';
import { isPlatformTransaction } from 'merchant/views/Transactions/v1/Payments/Utils/platformUtils';
import {
  defaultProps,
  App,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentDetails';
import { render, screen, fireEvent, waitFor, updateUseI18ServiceSpy } from 'test-utils';

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');

  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      refetch: jest.fn(),
      data: { appKey: 'test' },
      isLoading: false,
      error: {},
    }),
  };
});

jest.mock('merchant/views/Transactions/v1/Payments/Utils/platformUtils', () => ({
  isPlatformTransaction: jest.fn(),
}));

describe('PaymentDetails', () => {
  test('should render payment details', () => {
    render(<App />);
    expect(screen.getByText('Payment Id:')).toBeInTheDocument();
    expect(screen.getByText('paymentID')).toBeInTheDocument();
    expect(screen.getByText('PaymentPageDetails')).toBeInTheDocument();
  });

  test('should not render payment details when loading', () => {
    render(<App isLoading user={{ merchant: { currency: 'INR' } }} payment={{}} />);
    expect(screen.queryByText('Payment Id:')).not.toBeInTheDocument();
    expect(screen.queryByText('paymentID')).not.toBeInTheDocument();
    expect(screen.queryByText('PaymentPageDetails')).not.toBeInTheDocument();
  });

  test('should fetch ezetap app keys when payment is by card offline', async () => {
    render(<App payment={{ ...defaultProps.payment, method: 'card', receiver_type: 'pos' }} />);
    await waitFor(() => {
      expect(useQuery).toHaveBeenCalledWith(
        expect.objectContaining({
          queryKey: ['ezetap_appkey'],
          queryFn: expect.any(Function),
          enabled: false,
          refetchOnWindowFocus: false,
          staleTime: Infinity,
        }),
      );
    });
    expect(useQuery().refetch).toHaveBeenCalled();
  });

  describe('Payment error', () => {
    test('should render payment error code and its description', () => {
      render(<App />);
      expect(screen.getByText(defaultProps.payment.error_code)).toBeInTheDocument();
      expect(screen.getByText(defaultProps.payment.error_description)).toBeInTheDocument();
    });

    test('should render payment error source', () => {
      render(<App />);
      expect(screen.getByText(defaultProps.payment.error_source)).toBeInTheDocument();
    });

    test('should render payment error reason', () => {
      render(<App />);
      expect(screen.getByText(defaultProps.payment.error_reason)).toBeInTheDocument();
    });
  });

  describe('Scrolling', () => {
    test('should render scrolledToBottom as true when scrolled down', async () => {
      render(<App />);
      fireEvent.scroll(screen.getByTestId('payment-details'), {
        target: { scrollTop: 100 },
      });
      await waitFor(() => {
        expect(screen.getByText('scrolledToBottom: true')).toBeInTheDocument();
      });
    });

    test('should render scrolledToBottom as false when scrolled up', async () => {
      render(<App />);
      fireEvent.scroll(screen.getByTestId('payment-details'), {
        target: { scrollTop: 100 },
      });
      await waitFor(() => {
        expect(screen.getByText('scrolledToBottom: true')).toBeInTheDocument();
      });
      fireEvent.scroll(screen.getByTestId('payment-details'), {
        target: { scrollTop: 10 },
      });
      await waitFor(() => {
        expect(screen.getByText('scrolledToBottom: false')).toBeInTheDocument();
      });
    });
  });

  describe('Bank reference', () => {
    test('should render bank reference', () => {
      render(<App />);
      expect(
        screen.getByText(defaultProps.bankTransfer.details.bank_reference),
      ).toBeInTheDocument();
    });

    test('should not render bank reference when loading', () => {
      render(
        <App
          bankTransfer={{
            loading: true,
          }}
        />,
      );
      expect(
        screen.queryByText(defaultProps.bankTransfer.details.bank_reference),
      ).not.toBeInTheDocument();
    });
  });

  test('should render payment provider', () => {
    render(<App />);
    expect(screen.getByText(defaultProps.payment.provider)).toBeInTheDocument();
  });

  test('should render payment gateway provider', () => {
    render(<App />);
    expect(screen.getByText(defaultProps.payment.gateway_provider)).toBeInTheDocument();
  });

  describe('Triggering Refund', () => {
    test('should trigger refund modal if the transaction is offline and keys are present', () => {
      const openRefundModal = jest.fn();
      render(
        <App
          payment={{ ...defaultProps.payment, receiver_type: 'pos' }}
          openRefundModal={openRefundModal}
        />,
        {
          initialState: {
            session: {
              org: {
                features: [],
              },
              user: {
                findTag: () => false,
                isFeatureEnabled: () => true,
              },
            },
          },
        },
      );
      const RefundButton = screen.getByText('Refund Payment');
      fireEvent.click(RefundButton);
      expect(openRefundModal).toHaveBeenCalled();
    });

    test('should trigger modal to collect ezetap keys if the transaction is offline and keys are not present', () => {
      useQuery.mockReturnValueOnce({
        refetch: jest.fn(),
        data: {}, // Set a mock appKey for testing
        isLoading: false,
        error: {},
      });
      const collectEzetapKeys = jest.fn();
      render(
        <App
          payment={{ ...defaultProps.payment, receiver_type: 'pos' }}
          collectEzetapKeys={collectEzetapKeys}
        />,
        {
          initialState: {
            session: {
              org: {
                features: [],
              },
              user: {
                findTag: () => false,
                isFeatureEnabled: () => true,
              },
            },
          },
        },
      );
      const RefundButton = screen.getByText('Refund Payment');
      fireEvent.click(RefundButton);
      expect(collectEzetapKeys).toHaveBeenCalled();
    });
  });

  describe('Track payment details unmount', () => {
    test('should call paymentDetailsUnmount when component unmounts without qrPaymentDescription', () => {
      const { unmount } = render(<App />);
      unmount();
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'payments detail close',
        properties: {
          location: 'payments',
        },
        screen: 'payments Details',
        toLumberjack: true,
      });
    });

    test('should call paymentDetailsUnmount when component unmounts with qrPaymentDescription', () => {
      const { unmount } = render(
        <App payment={{ ...defaultProps.payment, description: 'QRv2 Payment' }} />,
      );
      unmount();
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'QR payments detail close',
        properties: {
          location: 'QR payments',
        },
        screen: 'QR payments Details',
        toLumberjack: true,
      });
    });
  });

  describe('Tests for disabled VA when payment method is bank transfer', () => {
    test('Capture/Refund should be hidden when payment method is bank_transfer and details are loading', () => {
      const payment = { ...defaultProps.payment, method: 'bank_transfer' };
      render(<App payment={payment} />);

      expect(screen.queryByText('Capture Payment')).not.toBeInTheDocument();
      expect(screen.queryByText('Refund Payment')).not.toBeInTheDocument();
    });

    test('Capture/Refund should be hidden when payment method is bank_transfer and va is inactive', () => {
      const payment = { ...defaultProps.payment, method: 'bank_transfer' };
      const bankTransfer = { loading: false, details: { virtual_account: { status: 'closed' } } };
      render(<App payment={payment} bankTransfer={bankTransfer} />);

      expect(screen.queryByText('Capture Payment')).not.toBeInTheDocument();
      expect(screen.queryByText('Refund Payment')).not.toBeInTheDocument();
    });

    test('Refund alert should be visible when payment method is bank_transfer and va is inactive', () => {
      const payment = { ...defaultProps.payment, method: 'bank_transfer' };
      const bankTransfer = { loading: false, details: { virtual_account: { status: 'closed' } } };
      render(<App payment={payment} bankTransfer={bankTransfer} />);

      expect(screen.getByText('This payment will be refunded within 72 hours')).toBeInTheDocument();
    });

    test('Refund alert should be hidden when payment method is bank_transfer and details are loading', () => {
      const payment = { ...defaultProps.payment, method: 'bank_transfer' };
      render(<App payment={payment} />);

      expect(
        screen.queryByText('This payment will be refunded within 72 hours'),
      ).not.toBeInTheDocument();
    });

    test('Refund alert should be hidden when payment method is other than bank_transfer', () => {
      const payment = { ...defaultProps.payment, method: 'upi_transfer' };
      const bankTransfer = { loading: false };
      render(<App payment={payment} bankTransfer={bankTransfer} />);

      expect(
        screen.queryByText('This payment will be refunded within 72 hours'),
      ).not.toBeInTheDocument();
    });

    test('Capture/Refund should be visible when payment method is other than bank_transfer', () => {
      const payment = { ...defaultProps.payment, method: 'upi_transfer' };
      const bankTransfer = { loading: false, details: { virtual_account: { status: 'closed' } } };
      render(<App payment={payment} bankTransfer={bankTransfer} />);

      expect(screen.getByText('Capture Payment')).toBeInTheDocument();
      expect(screen.getByText('Refund Payment')).toBeInTheDocument();
    });

    test('Capture/Refund should be visible when payment method is other than bank_transfer and bank details are loading', () => {
      const payment = { ...defaultProps.payment, method: 'upi_transfer' };
      const bankTransfer = { loading: true, details: { virtual_account: { status: 'closed' } } };
      render(<App payment={payment} bankTransfer={bankTransfer} />);

      expect(screen.getByText('Capture Payment')).toBeInTheDocument();
      expect(screen.getByText('Refund Payment')).toBeInTheDocument();
    });
  });
  describe.skip('Platform fee', () => {
    test('should render platform fee details instead of transfer if transaction type is platform and isRoutePartnershipEnabled is enabled', async () => {
      render(
        <App
          user={{
            ...defaultProps.user,
            isRoutePartnershipEnabled: true,
            isRoutePlusPartnershipsEnabled: true,
          }}
        />,
      );
      const text = await screen.getByText('Platform Fee');
      expect(text).toBeInTheDocument();
    });
  });

  describe('test suite for i18n orgs', () => {
    const payment = { ...defaultProps.payment, method: 'upi_transfer', currency: 'MYR' };
    const bankTransfer = { loading: true, details: { virtual_account: { status: 'closed' } } };
    const user = {
      merchant: {
        currency: 'MYR',
      },
    };
    const stateSpy = jest.spyOn(store, 'getState');

    stateSpy.mockReturnValue({
      session: {
        user: new User({
          merchant: { currency: 'MYR' },
          features: ['Marketplace'],
        }),
      },
    });
    test('hide PaymentRefund and Refund Payment components if refunds.refund tags are enabled', () => {
      updateUseI18ServiceSpy('refunds.refund');
      render(<App user={user} payment={payment} bankTransfer={bankTransfer} />);
      expect(screen.queryByText('PaymentRefund')).not.toBeInTheDocument();
      expect(screen.queryByText('Refund Payment')).not.toBeInTheDocument();
    });

    test('hide PaymentDisputes component if disputes.disputes tags are enabled', () => {
      updateUseI18ServiceSpy('disputes.disputes');
      render(<App user={user} payment={payment} bankTransfer={bankTransfer} />);
      expect(screen.queryByText('PaymentDisputes')).not.toBeInTheDocument();
    });

    test('hide PaymentTransfers component if payment_transfer.transfers tags are enabled', () => {
      updateUseI18ServiceSpy('payment_transfer.transfers');
      render(<App user={user} payment={payment} bankTransfer={bankTransfer} />);
      expect(screen.queryByText('PaymentTransfers')).not.toBeInTheDocument();
    });

    test('hide PaymentTransfers component if payment_transfer.transfers tags are enabled', () => {
      isPlatformTransaction.mockImplementation(() => false);
      const { container } = render(
        <App user={user} payment={payment} bankTransfer={bankTransfer} />,
      );
      const currencySymbol = container.querySelector('.rzp-currency');
      expect(currencySymbol).toHaveTextContent('RM');
    });
  });
});
