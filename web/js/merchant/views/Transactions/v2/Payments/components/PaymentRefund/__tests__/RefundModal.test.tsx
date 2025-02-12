import { fireEvent, screen, userEvent, waitFor } from 'test-utils';
import {
  defaultProps,
  payment,
  renderRefundModal,
  session,
  showWhenUtilSpy,
} from 'merchant/views/Transactions/v2/Payments/components/__tests__/mocks/fixtures/RefundModal';

describe('Refund Modal', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Alerts', () => {
    test('should render gateway Refund Support notice alert', () => {
      renderRefundModal({ payment: { ...payment, gateway_refund_support: false } });
      expect(screen.getByText('Refund Payment')).toBeInTheDocument();
      expect(
        screen.getByText('This payment was made more than 0 months ago, refund not supported.'),
      ).toBeInTheDocument();
    });

    test('should render instant refund support details', () => {
      renderRefundModal({
        payment: { ...payment, instant_refund_support: true, gateway_refund_support: false },
      });
      expect(
        screen.getByText(
          'This payment was made more than 0 months ago, you can only issue instant refund.',
        ),
      ).toBeInTheDocument();
    });

    test('should render payment refund dispute alert', () => {
      renderRefundModal({
        payment: {
          ...payment,
          disputes: {
            items: [
              {
                id: 'qw1efe3dwf',
                status: 'open',
              },
            ],
          },
        },
      });
      expect(
        screen.getByText(
          'There is dispute raised against this payment. Kindly check the dispute details before initiating a refund.',
        ),
      ).toBeInTheDocument();
    });

    test('should render payment refund dispute alert if many', () => {
      renderRefundModal();
      expect(
        screen.getByText(
          'There are disputes raised against this payment. Kindly check the dispute details before initiating a refund.',
        ),
      ).toBeInTheDocument();
    });

    test('should not show Instant Refund Alert if not instant_refund_support is enabled', () => {
      renderRefundModal({
        payment: {
          ...payment,
          instant_refund_support: true,
        },
      });
      showWhenUtilSpy.mockImplementation(() => false);
      expect(
        screen.queryByText(
          'Currently, Instant Refunds are available on TPV, netbanking and UPI only.',
        ),
      ).not.toBeInTheDocument();
    });

    test('should not show Instant Refund Alert if not instant_refund_support is enabled', () => {
      renderRefundModal(
        {
          payment: {
            ...payment,
            instant_refund_support: false,
          },
        },
        {
          session: {
            ...session,
            user: {
              ...session.user,
              isOptimizerEnabled: false,
            },
          },
        },
      );
      expect(
        screen.queryByText(
          'Currently, Instant Refunds are available on TPV, netbanking and UPI only.',
        ),
      ).toBeInTheDocument();
    });
  });

  describe('Refund', () => {
    test('should prefill amount to be refunded in refund amount input field', () => {
      renderRefundModal();
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      expect(refundInput).toHaveValue(String((payment.amount - payment.amount_refunded) / 100));
    });
    test('should allow to issue full refund', async () => {
      renderRefundModal();
      const reverseAll = screen.getByRole('checkbox', { name: /Reverse all/i });
      await userEvent.click(reverseAll);
      const commentInput = screen.getByPlaceholderText('Add comments');
      await userEvent.type(commentInput, 'comment description');
      const issueRefund = screen.getByRole('button', {
        name: /Issue full refund/,
      });
      await userEvent.click(issueRefund);
      await expect(defaultProps.fetchRefundFee).toHaveBeenCalled();
      await waitFor(() => {
        expect(screen.getByText('Refund successful')).toBeInTheDocument();
      });
    });
    test('should allow to issue full refund when instant refund is not enabled without reversals', async () => {
      renderRefundModal();
      const issueRefund = screen.getByRole('button', {
        name: /Issue full refund/,
      });
      await userEvent.click(issueRefund);
      await expect(defaultProps.fetchRefundFee).toHaveBeenCalled();
      await waitFor(() => {
        expect(screen.getByText('Refund successful')).toBeInTheDocument();
      });
    });
    test('should allow to issue partial refund with exact amount', async () => {
      renderRefundModal({
        payment: { ...payment, amount: 20000, amount_refunded: 0 },
      });
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await fireEvent.change(refundInput, { target: { value: '100' } });
      const issueRefund = screen.getByRole('button', {
        name: /Issue partial refund/,
      });
      await userEvent.click(issueRefund);
      await expect(defaultProps.fetchRefundFee).toHaveBeenCalled();
      await waitFor(() => {
        expect(screen.getByText('Refund successful')).toBeInTheDocument();
      });
    });
    test('should not allow to issue partial refund with fee reversals', async () => {
      renderRefundModal({
        payment: { ...payment, amount: 20000, amount_refunded: 0 },
      });
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await fireEvent.change(refundInput, { target: { value: '100' } });
      const reverseAll = screen.getByRole('checkbox', { name: /Reverse all/i });
      await userEvent.click(reverseAll);
      const issueRefund = screen.getByRole('button', {
        name: /Issue partial refund/,
      });
      await userEvent.click(issueRefund);
      await expect(defaultProps.fetchRefundFee).toHaveBeenCalled();
      expect(
        screen.getByText(
          "Reversals can't be automated when partially refunding a payment with more than 1 transfer to different linked accounts. Create reversals manually before attempting the refund.",
        ),
      ).toBeInTheDocument();
    });
  });

  describe('Amount Validations', () => {
    test('should not allow to issue refunds when amount is greater than the total refundable amount', async () => {
      renderRefundModal();
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await fireEvent.change(refundInput, { target: { value: '300' } });
      const issueRefund = screen.getByRole('button', {
        name: /Issue full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByText("Amount can't be greater than the total Refundable Amount (200)."),
      ).toBeInTheDocument();
    });

    test('should not allow to issue refunds when amount is less than 1 and currency is INR', async () => {
      renderRefundModal();
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await fireEvent.change(refundInput, { target: { value: '0.5' } });
      const issueRefund = screen.getByRole('button', {
        name: /Issue partial refund/,
      });
      await userEvent.click(issueRefund);
      expect(screen.getByText("Amount can't be less than 1")).toBeInTheDocument();
    });

    test('should not allow to issue refunds when amount is not a valid number with atmost 2 decimal places', async () => {
      renderRefundModal({
        payment: { ...payment, amount_refunded: 10.12222 },
      });
      const refundInput = screen.getByPlaceholderText('Enter the refund amount');
      await fireEvent.change(refundInput, { target: { value: '1.123' } });
      const issueRefund = screen.getByRole('button', {
        name: /Issue partial refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.getByText('Amount in selected currency must have upto 2 decimal places'),
      ).toBeInTheDocument();
    });
  });

  describe('International amount refund', () => {
    test('should not allow to issue refunds when last digit of amount is not zero for 3 decimal currencies', async () => {
      renderRefundModal({
        payment: { ...payment, currency: 'KWD', amount: 1001, amount_refunded: 0 },
      });
      const issueRefund = screen.getByRole('button', {
        name: /Issue partial refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.queryByText('Last digit should be 0 for three decimal currencies'),
      ).toBeInTheDocument();
    });

    test('should allow to issue refunds when last digit of amount is zero for 3 decimal currencies', async () => {
      renderRefundModal({
        payment: { ...payment, currency: 'KWD', amount: 21010, amount_refunded: 0 },
      });
      const issueRefund = screen.getByRole('button', {
        name: /Issue full refund/,
      });
      await userEvent.click(issueRefund);
      expect(
        screen.queryByText('Last digit should be 0 for three decimal currencies'),
      ).not.toBeInTheDocument();
    });
  });
});
