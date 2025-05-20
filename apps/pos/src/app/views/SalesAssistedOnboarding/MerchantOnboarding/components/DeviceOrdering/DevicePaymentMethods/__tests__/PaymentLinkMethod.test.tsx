import React from 'react';
import PaymentLinkMethod, {
  PaymentLinkMethodProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/PaymentLinkMethod/PaymentLinkMethod';
import { getModularConfig } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/__tests__/mocks/handlers';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';

const renderApp = (props: PaymentLinkMethodProps) => {
  render(<PaymentLinkMethod {...props} />);
};

describe('Test POS payment link method screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });
  const props: PaymentLinkMethodProps = {
    handleModularUpdate: jest.fn(),
    paymentLinkDetails: {
      paymentLinkStatus: '',
      paymentLinkCreatedAt: '1734588970',
      paymentLinkCompletedAt: '1734590036',
    },
    onResendBtnClick: jest.fn(),
    onCopyBtnClick: jest.fn(),
    amount: 3400,
    isUpdateModularLoading: false,
    isResendingLink: false,
  };

  test('should render generate link component when payment link is not created', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp(props);
    expect(
      screen.getByRole('button', {
        name: /generate link/i,
      }),
    ).toBeInTheDocument();
  });
  test('should render payment link component when payment link is created', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({
      ...props,
      paymentLinkDetails: { ...props.paymentLinkDetails, paymentLinkStatus: 'created' },
    });
    expect(screen.getByText(/Payment link created & shared/i)).toBeInTheDocument();
    expect(screen.getByText(/Thu, 19th dec’24 \| 6:16am/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /re-send link/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /copy link/i })).toBeInTheDocument();
    expect(screen.getByText(/payment pending/i)).toBeInTheDocument();
    expect(screen.getByText(/payment hasn't been processed yet/i)).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: /check payment status/i }));
    expect(props.handleModularUpdate).toHaveBeenCalledTimes(1);
  });

  test('should render order success screen if device step is completed or payment is successfull', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({
      ...props,
      paymentLinkDetails: { ...props.paymentLinkDetails, paymentLinkStatus: 'paid' },
    });
    expect(screen.getByText(/Payment successful/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Continue to next step/i })).toBeEnabled();
  });
});
