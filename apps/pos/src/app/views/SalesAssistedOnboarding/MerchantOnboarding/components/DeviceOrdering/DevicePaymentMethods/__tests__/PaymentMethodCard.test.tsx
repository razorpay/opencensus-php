import React from 'react';
import { getModularConfig } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/__tests__/mocks/handlers';
import { render, screen, server } from 'apps/pos/src/services/test/test-utils';
import PaymentMethodCard, {
  PaymentMethodCardProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/PaymentLinkMethod/PaymentMethodCard';
import { LinkIcon } from '@razorpay/blade/components';

const renderApp = (props) => {
  render(<PaymentMethodCard {...props} />);
};

describe('Test POS payment link method screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });

  const props: PaymentMethodCardProps = {
    paymentMethodIcon: LinkIcon,
    title: 'Payment link',
    value: 'payment_link',
    description: 'For Net banking, Cards, UPI',
    onClick: jest.fn(),
    isUpdateModularLoading: false,
    paymentStatus: 'pending',
  };

  test('should render card with payment type and description', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp(props);
    expect(screen.getByText(/payment link/i)).toBeInTheDocument();
    expect(screen.getByText(/for net banking, cards, upi/i)).toBeInTheDocument();
  });
  test('should render card with payment pending status', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp(props);
    expect(screen.getByText(/payment pending/i)).toBeInTheDocument();
  });
  test('should render card with payment expired status', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({ ...props, paymentStatus: 'expired' });
    expect(screen.getByText(/link expired/i)).toBeInTheDocument();
  });
  test('should render card with payment success status', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({ ...props, paymentStatus: 'success' });
    expect(screen.getByText(/payment successful/i)).toBeInTheDocument();
  });
  test('should render card with no badge when payment status is empty', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({ ...props, paymentStatus: 'cancelled' });
    expect(screen.getByText(/payment link/i)).toBeInTheDocument();
    expect(screen.getByText(/for net banking, cards, upi/i)).toBeInTheDocument();
    expect(screen.queryByText(/payment pending/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/link expired/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/payment successful/i)).not.toBeInTheDocument();
  });
});
