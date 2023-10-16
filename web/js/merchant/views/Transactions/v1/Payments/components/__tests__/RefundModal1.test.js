import '@testing-library/jest-dom/extend-expect';
import { getSplitzExperiments } from 'common/utils/__test__/mocks/fixtures';
import { rupeesToPaise } from 'common/utils/rzp-utils';
import User from 'merchant/models/User';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';
import {
  renderApp,
  payment,
  session,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/RefundModal';
import { screen, userEvent, delay, waitFor } from 'test-utils';
import * as showWhen from 'merchant/components/ShowWhen';

describe('RefundModal', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render refund payment details', () => {
    renderApp({
      initialState: {
        session: {
          ...session,
          user: new User({ merchants: {} }),
        },
      },
    });
    expect(screen.getByText('Refund Payment')).toBeInTheDocument();
    expect(
      screen.getByText('This payment was made more than 0 months ago, refund not supported'),
    ).toBeInTheDocument();
  });

  test('should call onMount when component mounts', () => {
    const onMount = jest.fn();
    renderApp({
      initialState: {
        payment: {
          ...payment,
          transfers: { items: [] },
          payment: {},
        },
        session: {
          ...session,
          user: new User({ merchants: {} }),
        },
      },
      props: {
        onMount,
      },
    });
    expect(onMount).toHaveBeenCalled();
  });

  test('should call onUnmount when component unmounts', () => {
    const onUnmount = jest.fn();
    const { unmount } = renderApp({
      initialState: {
        payment: {
          ...payment,
          payment: {},
        },
        session: {
          ...session,
          user: new User({ merchants: {} }),
        },
      },
      props: {
        onUnmount,
      },
    });
    unmount();
    expect(onUnmount).toHaveBeenCalled();
  });

  test('should render instant refund support details', () => {
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
          },
        },
      },
    });
    expect(
      screen.getByText(
        'This payment was made more than 0 months ago, you can only issue instant refund.',
      ),
    ).toBeInTheDocument();
  });

  describe('Payment refund disputes', () => {
    test('should render a payment refund dispute', () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              disputes: {
                items: [
                  {
                    id: 'qw1efe3dwf',
                    status: 'open',
                  },
                ],
              },
            },
          },
        },
      });
      expect(
        screen.getByText(
          `There is dispute raised against this payment. Kindly check the dispute details before initiating a refund.`,
        ),
      ).toBeInTheDocument();
    });

    test('should render payment refund disputes', () => {
      renderApp({
        initialState: {
          session: {
            ...session,
            user: new User({ merchants: {} }),
          },
        },
      });
      expect(
        screen.getByText(
          `There are disputes raised against this payment. Kindly check the dispute details before initiating a refund.`,
        ),
      ).toBeInTheDocument();
    });
  });

  test('should prefill amount to be refunded in refund amount input field', () => {
    renderApp({
      initialState: {
        session: {
          ...session,
          user: new User({ merchants: {} }),
        },
      },
    });
    const refundInput = screen.getByPlaceholderText('Enter the refund amount');
    expect(refundInput).toHaveValue(
      (payment.payment.amount - payment.payment.amount_refunded) / 100,
    );
  });

  test('should make refund api call only once on clicking the Yes, Refund multiple times', async () => {
    renderApp({
      initialState: {
        session: {
          ...session,
          user: new User({ merchants: {} }),
        },
      },
    });
    const issueRefund = screen.getByRole('button', {
      name: /Issue Full refund/,
    });
    await userEvent.click(issueRefund);

    const proceedToRefundButton = screen.getByRole('button', { name: 'Yes, Refund' });
    await userEvent.click(proceedToRefundButton);
    await userEvent.click(proceedToRefundButton);
    expect(payment.payment.refund).toHaveBeenCalledTimes(1);
    expect(issueRefund).toBeDisabled();
  });

  describe('Full refund', () => {
    test('should allow to issue full refund', async () => {
      renderApp({
        initialState: {
          session: {
            ...session,
            user: new User({ merchants: {} }),
          },
        },
      });
      const reverseAll = screen.getAllByRole('checkbox')[0];
      await userEvent.click(reverseAll);
      const addCommentBtn = screen.getByText('+ Add Comments(Optional)');
      await userEvent.click(addCommentBtn);
      const commentDescription = screen.getByPlaceholderText('Comment Description');
      await userEvent.type(commentDescription, 'comment description');
      // wait for the async commentDescription's measureHeight operation to be completed
      await delay();
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      await userEvent.unhover(issueRefund);
      expect(
        screen.getByRole('heading', {
          name: 'Do you want to refund this payment?',
        }),
      ).toBeInTheDocument();
      await userEvent.click(screen.getByRole('button', { name: 'Yes, Refund' }));
      expect(issueRefund).toBeDisabled();
      await waitFor(() => {
        expect(screen.getByText('Payment refunded')).toBeInTheDocument();
      });
    });

    test('should allow to issue full refund when instant refund is not enabled with reversals', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              gateway_refund_support: true,
            },
          },
        },
      });
      const reverseAll = screen.getAllByRole('checkbox')[0];
      await userEvent.click(reverseAll);
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByRole('heading', {
          name: 'Are you sure you want to refund this payment?',
        }),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          'Reversals will be automatically created for all transfers on this payment, before the refund',
        ),
      ).toBeInTheDocument();
      await userEvent.click(screen.getByRole('button', { name: 'Yes, Refund' }));
      expect(issueRefund).toBeDisabled();
      await waitFor(() => {
        expect(screen.getByText('Payment refunded')).toBeInTheDocument();
      });
    });

    test('should allow to issue full refund when instant refund is not enabled without reversals', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              gateway_refund_support: true,
            },
          },
        },
      });
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByRole('heading', {
          name: 'Are you sure you want to refund this payment?',
        }),
      ).toBeInTheDocument();
      expect(screen.getByText('The payment will be refunded in 5-7 days.')).toBeInTheDocument();
      await userEvent.click(screen.getByRole('button', { name: 'Yes, Refund' }));
      expect(issueRefund).toBeDisabled();
      await waitFor(() => {
        expect(screen.getByText('Payment refunded')).toBeInTheDocument();
      });
    });
  });

  describe('Partial refund', () => {
    test('should allow to issue partial refund with exact amount', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              amount: 20000,
              amount_refunded: 0,
            },
          },
        },
      });
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await userEvent.type(refundInput, '-100');
      const issueRefund = await screen.findByRole('button', {
        name: /Issue Partial refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByRole('heading', {
          name: 'Do you want to refund this payment?',
        }),
      ).toBeInTheDocument();
      await userEvent.click(screen.getByRole('button', { name: 'Yes, Refund' }));
      expect(payment.payment.refund).toHaveBeenCalledWith({
        amount: rupeesToPaise(100),
        comment: '',
        reverse_all: '0',
        speed: 'optimum',
      });
      expect(issueRefund).toBeDisabled();
      await waitFor(() => {
        expect(screen.getByText('Payment refunded')).toBeInTheDocument();
      });
    });

    test('should not allow to issue partial refund with reversals', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              amount: 20000,
              amount_refunded: 0,
            },
          },
        },
      });
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await userEvent.type(refundInput, '-100');
      const reverseAll = screen.getAllByRole('checkbox')[0];
      await userEvent.click(reverseAll);
      const issueRefund = screen.getByRole('button', {
        name: /Issue Partial refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByText(
          "Reversals can't be automated when partially refunding a payment with more than 1 transfer to different linked accounts. Create reversals manually before attempting the refund.",
        ),
      ).toBeInTheDocument();
    });
  });

  describe('Amount validation error', () => {
    test('should not allow to issue refunds when amount is greater than the total refundable amount', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              amount: 20000,
              amount_refunded: 0,
            },
          },
        },
      });
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await userEvent.type(refundInput, '200');
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByText("Amount can't be greater than the total Refundable Amount (200)."),
      ).toBeInTheDocument();
    });

    test('should not allow to issue refunds when amount is less than 1 and currency is INR', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              amount: -20000,
              amount_refunded: 0,
            },
          },
        },
      });
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(screen.getByText("Amount can't be less than 1")).toBeInTheDocument();
    });

    test('should not allow to issue refunds when amount is negative and currency is not INR', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              currency: null,
              amount: -20000,
              amount_refunded: 0,
            },
          },
        },
      });
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(screen.getByText("Amount can't be negative.")).toBeInTheDocument();
    });

    test('should not allow to issue refunds when amount is not a valid number with atmost 2 decimal places', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              currency: null,
              amount_refunded: 10.12222,
            },
          },
        },
      });

      const refundInput = screen.getByTestId('refund-amount');
      await userEvent.type(refundInput, '1.123');

      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByText('Amount can only be a Number with atmost 2 decimal places.'),
      ).toBeInTheDocument();
    });
  });

  describe('Tests for Internation amount refund', () => {
    beforeEach(() => {
      window.rzp_user = {
        splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support),
      };
    });

    test('should not allow to issue refunds when last digit of amount is not zero for 3 decimal currencies', async () => {
      renderApp({
        initialState: {
          session: {
            user: new User(),
          },
          payment: {
            ...payment,
            payment: {
              ...payment.payment,
              currency: 'KWD',
              amount: 1001,
              amount_refunded: 0,
            },
          },
        },
      });
      const issueRefund = screen.getByRole('button', {
        name: /Issue Full refund/,
      });
      await userEvent.click(issueRefund);
      await waitFor(() =>
        expect(
          screen.getByText('Last digit should be 0 for three decimal currencies'),
        ).toBeInTheDocument(),
      );
    });
  });

  test('should allow to issue refunds when last digit of amount is zero for 3 decimal currencies', async () => {
    renderApp({
      initialState: {
        session: {
          user: new User(),
        },
        payment: {
          ...payment,
          payment: {
            ...payment.payment,
            currency: 'KWD',
            amount: 21010,
            amount_refunded: 0,
          },
        },
      },
    });
    const issueRefund = screen.getByRole('button', {
      name: /Issue Full refund/,
    });
    await userEvent.click(issueRefund);
    expect(
      screen.queryByText('Last digit should be 0 for three decimal currencies'),
    ).not.toBeInTheDocument();
  });

  test('hide component if refunds.instant_refunds is enabled', () => {
    renderApp({
      initialState: {
        session: {
          user: new User({
            merchant: { country_code: 'MYR' },
          }),
        },
        payment: {
          ...payment,
          payment: {
            ...payment.payment,
            currency: null,
            amount_refunded: 10.12222,
            direct_settlement_refund: true,
            gateway_refund_support: true,
          },
        },
      },
      props: {
        current_balance: {
          loading: false,
        },
        refund_check_disabled: true,
        default_refund_speed: 'normal',
      },
    });
    jest.spyOn(showWhen, 'showWhenUtil').mockImplementation(() => false);

    expect(
      screen.queryByText(
        'Currently, Instant Refunds are available on TPV, netbanking, UPI and select credit cards and debit cards.',
      ),
    ).not.toBeInTheDocument();
  });
});
