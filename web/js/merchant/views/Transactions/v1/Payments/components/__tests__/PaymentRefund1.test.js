import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { useQuery } from '@tanstack/react-query';

import { analyticsTrack } from 'common/utils/analytics';
import {
  App,
  defaultProps,
  disputes,
  mockAbExperiments,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentRefund';
import { REFUND_STATUSES } from 'merchant/views/Transactions/v1/Payments/constants';
import { refund } from 'merchant/views/Transactions/v1/Refunds/__test__/mocks/fixtures';
import { render, screen, getByText, fireEvent, checkIfComponentIsEmpty } from 'test-utils';

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

describe('PaymentRefund', () => {
  beforeEach(() => {
    Object.assign(mockAbExperiments, {});
  });

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
      expect(screen.getAllByText('.')).toHaveLength(3);
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
      mockAbExperiments.payments_extra_refund_details = { variables: { result: 'on' } };
      const { rerender } = render(
        <App
          payment={{
            ...payment,
            disputes: {
              items: [disputes.items[0]],
            },
          }}
        />,
        {
          initialState: {
            session: {
              user: {
                isRefundAllowed: true,
                isOrgAllowedFunctionality: () => false,
              },
            },
          },
        },
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

    test('should render refund is in progress when refund is ongoing', () => {
      render(
        <App
          payment={{
            ...payment,
            status: 'captured',
            notes: {
              refund_status: REFUND_STATUSES.PROCESSING,
            },
            refund_status: null,
          }}
        />,
      );
      expect(screen.getByText('Refund is in Progress')).toBeInTheDocument();
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
        "We currently do not support refunds for Paytm 'Instant (beta)' integration. You can process this refund from your Paytm Business Dashboard";

      const link = getByText(container, 'Paytm Business Dashboard');

      expect(container.textContent.trim()).toEqual(expectedText);
      expect(link.getAttribute('href')).toBe('https://dashboard.paytm.com/');
    });

    describe('When refund is allowed', () => {
      mockAbExperiments.payments_extra_refund_details = { variables: { result: 'on' } };
      const renderApp = (ui) => {
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
      describe('Issue Refund button', () => {
        const checkIssueRefundButton = (props = {}) => {
          renderApp(
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

        test('should trigger refund modal if the transaction is offline and keys are present', () => {
          renderApp(
            <App
              payment={{
                ...payment,
                gateway_refund_support: true,
                refund_status: null,
                method: 'card',
                receiver_type: 'pos',
                analyticsPayload: jest.fn(),
              }}
            />,
          );

          const RefundButton = screen.getByText('Issue Refund');
          fireEvent.click(RefundButton);
          expect(defaultProps.openRefundModal).toHaveBeenCalled();
        });

        test('should trigger modal to collect ezetap keys if the transaction is offline and keys are not present', () => {
          const collectEzetapKeys = jest.fn();
          useQuery.mockReturnValueOnce({
            refetch: jest.fn(),
            data: {},
            isLoading: false,
            error: {},
          });
          renderApp(
            <App
              payment={{
                ...payment,
                gateway_refund_support: true,
                refund_status: null,
                method: 'card',
                receiver_type: 'pos',
                analyticsPayload: jest.fn(),
              }}
              collectEzetapKeys={collectEzetapKeys}
            />,
          );
          const RefundButton = screen.getByText('Issue Refund');
          fireEvent.click(RefundButton);
          expect(collectEzetapKeys).toHaveBeenCalled();
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

    describe('Optimizer - Refund gateway data info', () => {
      const renderComponent = (experimentResult) => {
        mockAbExperiments.refund_gateway_data = { variables: { result: experimentResult } };
        render(<App isOptimizerView={true} payment={payment} refunds={{ items: [refund] }} />);
        fireEvent.click(screen.getByText('Refund Details'));
        expect(defaultProps.onToggleClick).toHaveBeenCalledWith({
          disputes: { items: [] },
          refund_status: 'partial',
          status: 'captured',
        });
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
