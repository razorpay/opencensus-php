import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import {
  App,
  defaultProps,
  disputes,
  mockAbExperiments,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentRefund';
import { refund } from 'merchant/views/Transactions/v1/Refunds/__test__/mocks/fixtures';
import { render, screen, fireEvent } from 'test-utils';

describe('PaymentRefund', () => {
  beforeEach(() => {
    Object.assign(mockAbExperiments, {});
  });

  const renderApp = (ui) => {
    mockAbExperiments.payments_extra_refund_details = { variables: { result: 'on' } };
    render(ui, {
      initialState: {
        session: {
          user: {
            isRefundAllowed: true,
            isOrgAllowedFunctionality: () => true,
          },
        },
      },
    });
  };
  describe('When payment status is refunded', () => {
    const payment = {
      status: 'refunded',
      refund_status: 'full',
      disputes: {
        items: [],
      },
    };
    test('should render auto refunded payment details', () => {
      renderApp(<App payment={{ ...payment, refund_status: null }} />);
      expect(screen.getByText('Auto Refunded')).toBeInTheDocument();
      expect(
        screen.getByText(
          'Payment was not captured within 5 days of creation, hence it was automatically refunded.',
        ),
      ).toBeInTheDocument();
    });

    test('should render fully refunded payment details', () => {
      renderApp(<App payment={payment} />);
      expect(screen.getByText('Refund Reason')).toBeInTheDocument();
      expect(screen.getByText('Refund Reference Number')).toBeInTheDocument();
    });

    test('should render fully refunded payment details when there is a temporary debit', () => {
      renderApp(
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
      renderApp(
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
      renderApp(
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

    describe('Optimizer - Refund gateway data info', () => {
      const renderComponent = (experimentResult) => {
        mockAbExperiments.refund_gateway_data = { variables: { result: experimentResult } };
        render(<App isOptimizerView={true} payment={payment} refunds={{ items: [refund] }} />);
        fireEvent.click(screen.getByText('Refund Details'));
        expect(defaultProps.onToggleClick).toHaveBeenCalledWith(
          { disputes: { items: [] }, refund_status: 'full', status: 'refunded' },
          'normal',
        );
      };

      test('should render refund list with gateway data info if exp is "on" and "gateway_data" is not empty', () => {
        renderComponent('on');
        expect(screen.getByTestId('info-icon')).toBeInTheDocument();
      });

      test('should render refund list without gateway data info if exp is "off" and "gateway_data" is not empty', () => {
        renderComponent('off');
        expect(screen.queryByTestId('info-icon')).toBeNull();
      });

      test('should render refund list without gateway data info if exp is "on" but "gateway_data" is empty', () => {
        refund.gateway_data = [];
        renderComponent('on');
        expect(screen.queryByTestId('info-icon')).toBeNull();
      });
    });
  });
});
