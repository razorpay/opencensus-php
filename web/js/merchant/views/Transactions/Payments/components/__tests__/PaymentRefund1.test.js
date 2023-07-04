import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, getByText, fireEvent, checkIfComponentIsEmpty } from 'test-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { refund } from 'merchant/views/Transactions/Refunds/__test__/mocks/fixtures';
import {
  App,
  defaultProps,
  disputes,
} from 'merchant/views/Transactions/Payments/components/__tests__/mocks/fixtures/PaymentRefund';

describe('PaymentRefund', () => {
  test('should not render payment refund details when there is no payment status', () => {
    render(<App card={null} />);
    checkIfComponentIsEmpty();
  });

  test.each(['created', 'authorized', 'failed'])(
    'should render payment refund details when payment status is %s',
    (paymentStatus) => {
      render(
        <App
          payment={{
            status: paymentStatus,
          }}
        />,
      );
      expect(screen.getByText('Not Applicable')).toBeInTheDocument();
      expect(screen.getByText('Only captured payments can be refunded.')).toBeInTheDocument();
    },
  );

  describe('When payment status is captured', () => {
    const payment = {
      status: 'captured',
      refund_status: 'partial',
      disputes: {
        items: [],
      },
    };
    test('should render partial payment refund details', () => {
      const { rerender } = render(
        <App
          refunds={{
            loading: true,
          }}
          payment={payment}
        />,
      );
      expect(screen.getByText('Partially refunded in')).toBeInTheDocument();
      expect(screen.getAllByText('.')).toHaveLength(4);
      expect(screen.getByText('refunds')).toBeInTheDocument();
      rerender(
        <App
          refunds={{
            items: [refund, refund],
          }}
          payment={payment}
        />,
      );
      expect(screen.getByText('Partially refunded in')).toBeInTheDocument();
      expect(screen.getByText('2 refunds')).toBeInTheDocument();
    });

    test('should render open disputes', () => {
      const { rerender } = render(
        <App
          payment={{
            ...payment,
            disputes: {
              items: [disputes.items[0]],
            },
          }}
        />,
      );
      expect(
        screen.getByText('Refunds are disabled as there is an open dispute on this payment'),
      ).toBeInTheDocument();
      rerender(
        <App
          payment={{
            ...payment,
            disputes,
          }}
        />,
      );
      expect(
        screen.getByText('Refunds are disabled as there are open disputes on this payment'),
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
      expect(defaultProps.onToggleClick).toHaveBeenCalledWith({
        disputes: { items: [] },
        refund_status: 'partial',
        status: 'captured',
      });
    });

    test('should render non-partial payment refund details', () => {
      render(
        <App
          payment={{
            ...payment,
            refund_status: null,
          }}
        />,
      );
      expect(screen.getByText('No refunds issued yet')).toBeInTheDocument();
    });

    test('should render non-partial payment refund details for optimizer', () => {
      render(
        <App
          payment={{
            ...payment,
            refund_status: null,
            optimizer_provider: 'ABC123',
            gateway_refund_support: true,
          }}
        />,
      );
      expect(screen.getByText('No refunds issued yet')).toBeInTheDocument();
    });

    test('should render seamless option is not enabled details', () => {
      // a new Date object called now, represents the current date and time to ensure created_at is always less than six months
      const now = new Date();
      // valueOf() of the now object is in milliseconds so dividing by 1000 to get seconds
      const created_at = now.valueOf() / 1000;

      const { container } = render(
        <App
          payment={{
            ...payment,
            refund_status: null,
            optimizer_provider: 'paytm',
            gateway_refund_support: false,
            created_at,
          }}
        />,
      );

      const expectedText =
        "We currently do not support refunds for Paytm 'Instant (beta)' integration. You can process this refund from your Paytm Business Dashboard ";

      const link = getByText(container, 'Paytm Business Dashboard');

      expect(container.textContent).toEqual(expectedText);
      expect(link.getAttribute('href')).toBe('https://dashboard.paytm.com/');
    });

    describe('When refund is allowed', () => {
      describe('Issue Refund button', () => {
        const checkIssueRefundButton = (props = {}) => {
          render(
            <App
              payment={{
                ...payment,
                gateway_refund_support: true,
                refund_status: null,
                analyticsPayload: jest.fn(),
              }}
              {...props}
            />,
          );
          expect(screen.getByText('Issue Refund')).toBeInTheDocument();
        };

        test('should render it when refund status is non-partial', () => {
          checkIssueRefundButton();
        });

        test('should open refund modal when it is clicked', () => {
          checkIssueRefundButton();
          fireEvent.click(screen.getByText('Issue Refund'));
          expect(defaultProps.openRefundModal).toHaveBeenCalled();
        });

        test('should call analytics track event when it is clicked with QR code as true', () => {
          checkIssueRefundButton({ isQrCode: true });
          fireEvent.click(screen.getByText('Issue Refund'));
          expect(analyticsTrack).toHaveBeenCalledWith({
            objectName: 'qr payment detail refund issued',
            actionName: 'clicked',
            screen: 'qrcode payment detail',
          });
        });
      });
    });

    describe('When gateway refund support is not enabled for optimizer provider', () => {
      const issueRefundButtonHidden = (props = {}) => {
        render(
          <App
            {...props}
            payment={{
              ...payment,
              optimizer_provider: 'ABC123',
              gateway_refund_support: false,
            }}
          />,
        );
      };

      test('should not render issue refund button', () => {
        issueRefundButtonHidden();
        expect(screen.queryByText('Issue Refund')).not.toBeInTheDocument();
      });
    });
  });
});
