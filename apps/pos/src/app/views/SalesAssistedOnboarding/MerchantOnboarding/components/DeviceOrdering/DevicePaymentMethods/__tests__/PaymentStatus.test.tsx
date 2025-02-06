import React from 'react';
import PaymentStatus, {
  PaymentStatusProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/PaymentLinkMethod/PaymentStatus';
import {
  getModularConfig,
  updateModularConfig,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/__tests__/mocks/handlers';
import { render, screen, server } from 'apps/pos/src/services/test/test-utils';

const renderApp = (props) => {
  render(<PaymentStatus {...props} />);
};

describe('Test POS payment link method screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });
  const props: PaymentStatusProps = {
    onResendBtnClick: jest.fn(),
    onCopyBtnClick: jest.fn(),
    paymentLinkCreatedAt: '1734588970',
    paymentLinkCompletedAt: '1734590036',
    paymentLinkStatus: 'created',
    isResendingLink: false,
    amount: 100,
  };

  test('should render timeline with pending status when payment link is created', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp(props);
    expect(screen.getByText(/payment link created & shared/i)).toBeInTheDocument();
    expect(screen.getByText(/Thu, 19th dec’24 \| 6:16am/i)).toBeInTheDocument();
    screen.logTestingPlaygroundURL();
    expect(screen.getByText(/amount to be paid:/i)).toBeInTheDocument();
    expect(screen.getByText(/100/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /re-send link/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /copy link/i })).toBeInTheDocument();
    expect(screen.getByText(/payment pending/i)).toBeInTheDocument();
    expect(screen.getByText(/payment hasn't been processed yet/i)).toBeInTheDocument();
  });

  test('should render timeline with expired status when payment link is expired', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({
      ...props,
      paymentLinkStatus: 'expired',
    });
    expect(screen.getByText(/payment link created & shared/i)).toBeInTheDocument();
    expect(screen.getByText(/Thu, 19th dec’24 \| 6:16am/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /re-send link/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /copy link/i })).not.toBeInTheDocument();
    expect(screen.getByText(/payment link expired/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /the payment link has exceeded its 30-day validity period\. please generate a new payment link\./i,
      ),
    ).toBeInTheDocument();
  });

  test('should render timeline when link is paid', async () => {
    server.use(updateModularConfig({ type: 'success' }));
    renderApp({
      ...props,
      paymentLinkStatus: 'paid',
    });
    expect(screen.queryByText(/Generate Payment link/i)).not.toBeInTheDocument();
    expect(screen.getByText(/Payment link created & shared/i)).toBeInTheDocument();
    expect(screen.getByText(/Thu, 19th dec’24 \| 6:16am/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /re-send link/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /copy link/i })).not.toBeInTheDocument();
    expect(screen.getByText(/payment success/i)).toBeInTheDocument();
    expect(screen.getByText(/payment is successful/i)).toBeInTheDocument();
  });
});
