import '@testing-library/jest-dom/extend-expect';
import { screen, userEvent } from 'test-utils';
import {
  renderApp,
  payment,
  session,
  showWhenUtilSpy,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/RefundModal';
import User from 'merchant/models/User';
import { PAYMENT_STATUS } from 'merchant/views/Transactions/v1/Payments/constants';

jest.mock('react-query', () => ({
  useQuery: jest.fn().mockReturnValue({
    data: { appKey: 'testAppKey', username: 'testUsername' },
  }),
}));

describe('RefundModal', () => {
  describe('Instant refund', () => {
    beforeEach(() => {
      showWhenUtilSpy.mockImplementation(
        ({ featureEnabled }) => featureEnabled !== 'disable_instant_refunds',
      );
    });
    describe('Instant refund checkbox', () => {
      test('should not show instant refund checkbox when instant refund is disabled', () => {
        showWhenUtilSpy.mockImplementation(
          ({ featureEnabled }) => featureEnabled === 'disable_instant_refunds',
        );
        renderApp({
          initialState: {
            session: {
              ...session,
              user: new User({ merchants: {} }),
            },
          },
        });
        const instantRefundInput = screen.queryAllByRole('checkbox')[1];
        expect(instantRefundInput).toBeFalsy();
      });

      test('should show and allow instant refund checkbox to be toggled when instant refund is enabled', async () => {
        renderApp({
          initialState: {
            session: {
              user: new User(),
            },
            payment: {
              ...payment,
              payment: {
                ...payment.payment,
                instant_refund_support: true,
                amount: 10,
              },
              current_balance: {
                data: {
                  refund_credits: 1000,
                },
              },
            },
          },
        });
        const instantRefundInput = screen.getAllByRole('checkbox')[1];
        expect(instantRefundInput).toBeEnabled();
        await userEvent.click(instantRefundInput);
        expect(instantRefundInput.checked).toEqual(false);
        await userEvent.click(instantRefundInput);
        expect(instantRefundInput.checked).toEqual(true);
      });
    });

    test('should show loading on the screen when instant refund is enabled and current balance is loading', () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              instant_refund_support: true,
              amount: 10,
            },
            current_balance: {
              loading: true,
            },
          },
        },
      });
      expect(screen.getByText('Loading...')).toBeInTheDocument();
    });

    test("should show add credits link when instant refund is enabled but couldn't be supported instantly", () => {
      renderApp({
        initialState: {
          session: {
            user: new User({
              experiments: {
                refund_credit_self_serve: {
                  result: 'on',
                },
                refund_source_fallback_enabled: {
                  result: 'on',
                },
              },
            }),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              instant_refund_support: false,
              amount: 1000,
            },
            current_balance: {
              data: {
                refund_credits: 100,
              },
            },
          },
        },
      });

      expect(screen.getByText(/Add Credits/)).toBeInTheDocument();
    });

    test("should show add funds link when instant refund is enabled but couldn't be supported instantly", () => {
      renderApp({
        initialState: {
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              instant_refund_support: false,
              amount: 1000,
            },
            current_balance: {
              data: {
                refund_credits: 100,
              },
            },
          },
          session: {
            ...session,
            user: new User({ merchants: {} }),
          },
        },
      });
      expect(screen.getByText(/Add Funds/)).toBeInTheDocument();
    });

    test('should show proper message when instant refund is enabled but instant_refund_support is false', () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              instant_refund_support: false,
              amount: 10,
            },
            current_balance: {
              data: {
                refund_credits: 1000,
              },
            },
          },
        },
      });
      expect(
        screen.getByText(
          'Currently, Instant Refunds are available on TPV, netbanking, UPI and select credit cards and debit cards.',
        ),
      ).toBeInTheDocument();
    });
  });
  describe('Refund and void for offline card transactions', () => {
    const renderAppWithDefautProps = (props) => {
      return renderApp({
        initialState: {
          session: {
            ...session,
            user: new User({ merchants: {} }),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              gateway_refund_support: true,
              receiver_type: 'pos',
              method: 'card',
              ...props,
            },
          },
        },
      });
    };

    test('not allowing partial refunds for offline card transactions', () => {
      renderAppWithDefautProps({ status: PAYMENT_STATUS.AUTHORIZED });
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      expect(refundInput).toHaveAttribute('readOnly', '');
    });

    test('should fully refund offline(pos) transaction with the exact amount', async () => {
      renderAppWithDefautProps();
      const issueRefund = await screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByRole('heading', {
          name: 'Are you sure you want to refund this payment?',
        }),
      ).toBeInTheDocument();
      await userEvent.click(screen.getByRole('button', { name: 'Yes, Refund' }));
      expect(payment.payment.refundOfflinePayment).toHaveBeenCalledWith({
        appKey: 'testAppKey',
        username: 'testUsername',
        amount: payment.payment.amount - payment.payment?.amount_refunded, // full refund as partial refunds are disabled for offline card transactions
        externalRefNumber: payment.payment.notes.external_ref_id1,
      });
    });

    test('should make call to void transaction if the status is authorized and done by pos device', async () => {
      renderAppWithDefautProps({ status: PAYMENT_STATUS.AUTHORIZED });
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      const proceedToRefundButton = screen.getByRole('button', { name: 'Yes, Refund' });
      await userEvent.click(proceedToRefundButton);
      expect(payment.payment.voidPayment).toHaveBeenCalledTimes(1);
    });

    test('should void the transaction if the status is authorized and done by pos device with exact payload', async () => {
      renderAppWithDefautProps({ status: PAYMENT_STATUS.AUTHORIZED });
      const issueRefund = await screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByRole('heading', {
          name: 'Are you sure you want to refund this payment?',
        }),
      ).toBeInTheDocument();
      await userEvent.click(screen.getByRole('button', { name: 'Yes, Refund' }));
      expect(payment.payment.voidPayment).toHaveBeenCalledWith({
        appKey: 'testAppKey',
        username: 'testUsername',
        amount: payment.payment.amount - payment.payment?.amount_refunded, // full refund as partial refunds are disabled for offline card transactions
        txnId: payment.payment.notes.txn_id,
      });
    });
  });
});
