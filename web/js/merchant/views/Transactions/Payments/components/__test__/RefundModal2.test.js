import '@testing-library/jest-dom/extend-expect';
import { screen, userEvent } from 'test-utils';
import {
  renderApp,
  payment,
  session,
  showWhenUtilSpy,
} from 'merchant/views/Transactions/Payments/components/__test__/mocks/fixtures/RefundModal';

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
        renderApp();
        const instantRefundInput = screen.queryAllByRole('checkbox')[1];
        expect(instantRefundInput).toBeFalsy();
      });

      test('should show and allow instant refund checkbox to be toggled when instant refund is enabled', async () => {
        renderApp({
          initialState: {
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
            user: {
              merchants: {},
            },
          },
        },
      });
      expect(screen.getByText(/Add Funds/)).toBeInTheDocument();
    });

    test('should show proper message when instant refund is enabled but instant_refund_support is false', () => {
      renderApp({
        initialState: {
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
});
