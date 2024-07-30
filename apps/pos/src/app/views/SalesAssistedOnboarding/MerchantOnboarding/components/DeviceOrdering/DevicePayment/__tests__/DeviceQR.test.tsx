import React from 'react';
import DeviceQR from '../DeviceQR';
import {
  render,
  screen,
  userEvent,
  waitFor,
  waitForElementToBeRemoved,
} from 'apps/pos/src/services/test/test-utils';

const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

const defaultProps = {
  qrCodeIntent: 'upi://pay?ver=01&mode=22&pa=kmk778.rzp@icici&pn=Kmk&qr',
  merchantName: 'Test Merchant',
  amount: 3000,
  isUpdateModularLoading: false,
  handleModularUpdate: jest.fn((payload) => {
    payload.modular_callback();
  }),
};

const renderApp = (props = {}) => {
  render(<DeviceQR {...defaultProps} {...props} />);
};

describe('DeviceQR', () => {
  test('should render DeviceQR component', async () => {
    renderApp();
    await waitForElementToBeRemoved(() => screen.getByText(/Generating QR Code/));
    expect(screen.getByText('Test Merchant')).toBeInTheDocument();
    expect(screen.getByText('Powered by')).toBeInTheDocument();
    expect(screen.getByText('Scan QR to Make Payment')).toBeInTheDocument();
    expect(screen.getByText('SCAN & PAY WITH ANY UPI APP')).toBeInTheDocument();
    expect(screen.getByText('3,000.00')).toBeInTheDocument();
  });

  test('should render QR code image', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByAltText('qr-code')).toBeInTheDocument();
    });
  });

  test('should render error message if QR code generation fails', async () => {
    renderApp({ qrCodeIntent: 1231231313 });
    expect(screen.getByText(/Generating QR Code/)).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText('Failed to generate QR')).toBeInTheDocument();
    });
  });

  test('should call modular with correct params on status check attempt', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Check Payment Status'));
    expect(defaultProps.handleModularUpdate).toHaveBeenCalledWith({
      check_qr_status_field: expect.any(Number),
      modular_callback: expect.any(Function),
    });
    await waitFor(() => {
      expect(screen.getByText('Payment Pending')).toBeInTheDocument();
    });
  });

  test('should navigate back on back press', async () => {
    renderApp();
    await userEvent.click(screen.getByLabelText('back-btn'));
    expect(mockNavigate).toHaveBeenCalledWith(-1);
  });
});
