import User from 'merchant/models/User';
import {
  initialState,
  payment,
  renderRefundModal,
} from 'merchant/views/Transactions/v2/Payments/components/__tests__/mocks/fixtures/RefundModal';
import { screen, userEvent } from 'test-utils';

describe('Instant Refund', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should show and allow instant refund checkbox to be toggled when instant refund is enabled', async () => {
    renderRefundModal(
      {
        payment: { ...payment, instant_refund_support: true, amount: 10 },
      },
      {
        ...initialState,
        payment: {
          ...initialState.payment,
          current_balance: {
            data: {
              refund_credits: 1000,
            },
          },
        },
      },
    );
    const instantRefundInput = screen.getByRole('checkbox', { name: /Refund instantly/i });
    expect(instantRefundInput).toBeEnabled();
    await userEvent.click(instantRefundInput);
    expect(instantRefundInput).toBeChecked();
    await userEvent.click(instantRefundInput);
    expect(instantRefundInput).not.toBeChecked();
  });
  test('calculate refund fee and total deductions correctly', async () => {
    renderRefundModal(
      {
        payment: { ...payment, instant_refund_support: true, amount: 4000 },
        fetchRefundFee: jest.fn(() =>
          Promise.resolve({
            data: {
              fee: 590,
              tax: 90,
            },
          }),
        ),
      },
      {
        ...initialState,
        payment: {
          ...initialState.payment,
          current_balance: {
            data: {
              refund_credits: 5000,
            },
          },
        },
      },
    );
    const instantRefundInput = screen.getByRole('checkbox', { name: /Refund instantly/i });
    await userEvent.click(instantRefundInput);
    const instantRefundFee = screen.getByTestId('instant-refund-fee');
    expect(instantRefundFee).toHaveTextContent('₹5.90');
    const totalAmountDeducted = screen.getByTestId('total-amount-deducted');
    expect(totalAmountDeducted).toHaveTextContent('₹45.90');
  });
  test("should show add credits link when instant refund is enabled but couldn't be supported instantly", () => {
    renderRefundModal(
      {
        payment: {
          ...payment,
          instant_refund_support: false,
          direct_settlement_refund: false,
          amount: 1000,
        },
      },
      {
        ...initialState,
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
          ...initialState.payment,
          current_balance: {
            data: {
              refund_credits: 900,
            },
          },
        },
      },
    );
    expect(screen.getByText(/Add Credits/)).toBeInTheDocument();
  });
  test("should show add funds link when instant refund is enabled but couldn't be supported instantly", () => {
    renderRefundModal(
      {
        payment: {
          ...payment,
          instant_refund_support: false,
          direct_settlement_refund: false,
          amount: 1000,
        },
      },
      {
        ...initialState,
        session: {
          user: new User({
            experiments: {
              refund_credit_self_serve: {
                result: 'off',
              },
              refund_source_fallback_enabled: {
                result: 'off',
              },
            },
          }),
        },
        payment: {
          ...initialState.payment,
          current_balance: {
            data: {
              refund_credits: 100,
            },
          },
        },
      },
    );
    expect(screen.getByText(/Add Funds/)).toBeInTheDocument();
  });
});
