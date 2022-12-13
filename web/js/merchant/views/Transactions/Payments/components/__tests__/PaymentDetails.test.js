import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import {
  defaultProps,
  App,
} from 'merchant/views/Transactions/Payments/components/__tests__/mocks/fixtures/PaymentDetails';
import { analyticsTrack } from 'common/utils/analytics';

describe('PaymentDetails', () => {
  test('should render payment details', () => {
    render(<App />);
    expect(screen.getByText('Payment Id:')).toBeInTheDocument();
    expect(screen.getByText('paymentID')).toBeInTheDocument();
    expect(screen.getByText('PaymentPageDetails')).toBeInTheDocument();
  });

  test('should not render payment details when loading', () => {
    render(<App isLoading user={{}} payment={{}} />);
    expect(screen.queryByText('Payment Id:')).not.toBeInTheDocument();
    expect(screen.queryByText('paymentID')).not.toBeInTheDocument();
    expect(screen.queryByText('PaymentPageDetails')).not.toBeInTheDocument();
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
});
