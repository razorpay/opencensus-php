import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import { refund } from 'merchant/views/Transactions/v1/Refunds/__test__/mocks/fixtures';
import {
  App,
  defaultProps,
  disputes,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentRefund';

describe('PaymentRefund', () => {
  describe('When payment status is refunded', () => {
    const payment = {
      status: 'refunded',
      refund_status: 'full',
      disputes: {
        items: [],
      },
    };
    test('should render auto refunded payment details', () => {
      render(<App payment={{ ...payment, refund_status: null }} />);
      expect(screen.getByText('Auto Refunded')).toBeInTheDocument();
      expect(
        screen.getByText(
          'Payment was not captured within 5 days of creation, hence it was automatically refunded.',
        ),
      ).toBeInTheDocument();
    });

    test('should render fully refunded payment details', () => {
      render(<App payment={payment} />);
      expect(screen.getByText('Refund Reason')).toBeInTheDocument();
      expect(screen.getByText('Refund Reference Number')).toBeInTheDocument();
    });

    test('should render fully refunded payment details when there is a temporary debit', () => {
      render(
        <App
          payment={{
            ...payment,
            disputes,
          }}
        />,
      );
      expect(
        screen.getByText(
          'This is a temporary debit. It will be reversed after the issuing bank closes the chargeback in your favor.',
        ),
      ).toBeInTheDocument();
    });

    test('should render fully refunded payment details when error reason is avs_failure', () => {
      render(
        <App
          payment={{
            ...payment,
            error_reason: 'avs_failure',
          }}
        />,
      );
      expect(screen.getAllByText('Refund Reason')[1]).toBeInTheDocument();
      expect(
        screen.getByText('Payment auto refunded because of billing address mismatch'),
      ).toBeInTheDocument();
    });

    test('should render toggleable refund list', () => {
      render(
        <App
          refunds={{
            items: [refund],
          }}
          payment={payment}
        />,
      );
      fireEvent.click(screen.getByText('Refund Details'));
      expect(defaultProps.onToggleClick).toHaveBeenCalledWith(
        { disputes: { items: [] }, refund_status: 'full', status: 'refunded' },
        'normal',
      );
    });
  });
});
