import React from 'react';
import DevicePaymentMethods, {
  DevicePaymentMethodsProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/DevicePaymentMethods';
import {
  getModularConfig,
  updateModularConfig,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/__tests__/mocks/handlers';
import { render, screen, server, userEvent } from 'apps/pos/src/services/test/test-utils';

const renderApp = (props) => {
  render(<DevicePaymentMethods {...props} />);
};

describe('Test POS device payment methods', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });
  const props: DevicePaymentMethodsProps = {
    isModuleLoading: {
      isPaymentLinkLoading: false,
      isQrCodeLoading: false,
    },
    title: 'Payment Options',
    onClickScanAndPay: jest.fn(),
    onClickPaymentLink: jest.fn(),
    paymentMethods: [
      {
        label: 'Scan and Pay',
        value: 'qr_code',
        helpText: 'Pay using QR code',
      },
      {
        label: 'Payment Link',
        value: 'payment_link',
        helpText: 'For Net banking, Cards, UPI',
      },
    ],
    isUpdateModularLoading: false,
    paymentLinkStatus: '',
    qrPaymentStatus: '',
    isPaymentLinkEnabled: true,
  };

  test('should render title and payment options when available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp(props);
    expect(screen.getByText(/Payment Options/i)).toBeInTheDocument();
    expect(screen.getByText(/Pay using QR code/i)).toBeInTheDocument();
    expect(screen.getByText(/Payment link/i)).toBeInTheDocument();
    expect(screen.getByText(/For Net banking, Cards, UPI/i)).toBeInTheDocument();
  });

  test('should render title and no payment options when options not available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({ ...props, paymentMethods: [] });
    expect(screen.getByText(/Payment Options/i)).toBeInTheDocument();
    expect(screen.queryByText(/Scan and pay/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Pay using QR code/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Payment link/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/For Net banking, Cards, UPI/i)).not.toBeInTheDocument();
  });

  test('should render loader when payment link card clicked', async () => {
    server.use(updateModularConfig({ type: 'success' }));
    renderApp(props);
    await userEvent.click(screen.getByText(/Payment link/i));
    expect(props.onClickPaymentLink).toHaveBeenCalledTimes(1);
  });
});
