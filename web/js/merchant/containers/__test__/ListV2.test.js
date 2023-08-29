import {
  PAYMENT_LINKS,
  defaultProps,
  renderApp,
} from 'merchant/containers/__test__/mocks/fixtures/ListV2';
import { BATCH_TYPE } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { screen, waitFor } from 'test-utils';

describe('BatchList', () => {
  beforeEach(() => {
    window.rzpQ = {
      onbr: () => ({ success: jest.fn() }),
    };
  });

  test('should show "Payment Link" tab if batch type is not "payment_page', async () => {
    renderApp({
      props: defaultProps,
    });
    await waitFor(() => {
      expect(screen.getByText(PAYMENT_LINKS)).toBeInTheDocument();
    });
  });
  test('should not show "Payment Link" tab if batch type is "payment_page"', async () => {
    renderApp({
      props: {
        ...defaultProps,
        batchType: BATCH_TYPE,
        user: {
          ...defaultProps.user,
          isPaymentLinkBatchEnabledForSellerAppRole: true,
        },
      },
    });
    await waitFor(() => {
      expect(screen.queryByText(PAYMENT_LINKS)).not.toBeInTheDocument();
    });
  });
});
