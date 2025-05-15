import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import SharePaymentHandleModal from '../SharePaymentHandleModal';
import { useStore } from '@federated/apps/shell/commonStore';
import { isMobileDevice } from '@libs/shared-utils';

// Mock the dependencies
jest.mock('@federated/apps/shell/commonStore');
jest.mock('@libs/shared-utils');
jest.mock('@razorpay/blade/components', () => ({
  Button: ({ children, ...props }: any) => <button {...props}>{children}</button>,
  TextInput: ({ value, onChange, label, ...props }: any) => (
    <input
      {...props}
      defaultValue={value}
      onChange={(e) => onChange?.({ value: e.target.value })}
      aria-label={label}
      data-testid={props['data-testid']}
    />
  ),
  Box: ({ children, ...props }: any) => <div {...props}>{children}</div>,
  Text: ({ children, ...props }: any) => <span {...props}>{children}</span>,
  Divider: (props: any) => <hr {...props} />,
}));
jest.mock('@libs/shared-ui', () => ({
  useModalComponents: () => ({
    Modal: ({ children, ...props }: any) => (
      <div data-testid="modal" {...props}>
        {children}
      </div>
    ),
    ModalHeader: ({ children, ...props }: any) => (
      <div data-testid="modal-header" {...props}>
        {children}
      </div>
    ),
    ModalBody: ({ children, ...props }: any) => (
      <div data-testid="modal-body" {...props}>
        {children}
      </div>
    ),
    ModalFooter: ({ children, ...props }: any) => (
      <div data-testid="modal-footer" {...props}>
        {children}
      </div>
    ),
  }),
}));

describe('SharePaymentHandleModal', () => {
  const mockOnDismiss = jest.fn();
  const mockOnPaymentShare = jest.fn();
  const mockShowNotification = jest.fn();
  const mockPaymentUrl = 'https://example.com/pay';

  beforeEach(() => {
    jest.clearAllMocks();
    (useStore as unknown as jest.Mock).mockReturnValue(mockShowNotification);
    (isMobileDevice as jest.Mock).mockReturnValue(false);
  });

  it('renders correctly with all props', () => {
    render(
      <SharePaymentHandleModal
        onDismiss={mockOnDismiss}
        paymentUrl={mockPaymentUrl}
        onPaymentShare={mockOnPaymentShare}
      />,
    );

    expect(screen.getByTestId('modal')).toBeInTheDocument();
    expect(screen.getByTestId('modal-header')).toBeInTheDocument();
    expect(screen.getByTestId('modal-body')).toBeInTheDocument();
    expect(screen.getByTestId('modal-footer')).toBeInTheDocument();
    expect(screen.getByLabelText('Your handle')).toHaveValue(mockPaymentUrl);
    expect(screen.getByLabelText('Enter amount (optional)')).toBeInTheDocument();
  });

  it('handles amount input correctly', () => {
    render(
      <SharePaymentHandleModal
        onDismiss={mockOnDismiss}
        paymentUrl={mockPaymentUrl}
        onPaymentShare={mockOnPaymentShare}
      />,
    );

    const amountInput = screen.getByLabelText('Enter amount (optional)');
    fireEvent.change(amountInput, { target: { value: '1000' } });
    expect(amountInput).toHaveValue(1000);
  });

  it('shows success notification and copies link on successful share', async () => {
    mockOnPaymentShare.mockResolvedValueOnce(true);

    render(
      <SharePaymentHandleModal
        onDismiss={mockOnDismiss}
        paymentUrl={mockPaymentUrl}
        onPaymentShare={mockOnPaymentShare}
      />,
    );

    const shareButton = screen.getByRole('button', { name: 'Copy Link' });
    fireEvent.click(shareButton);

    await waitFor(() => {
      expect(mockOnPaymentShare).toHaveBeenCalledWith({ amount: '' });
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });

    // Wait for the copied state to reset
    await waitFor(
      () => {
        expect(screen.getByText('Copy Link')).toBeInTheDocument();
      },
      { timeout: 2500 },
    );
  });

  it('handles share with amount correctly', async () => {
    mockOnPaymentShare.mockResolvedValueOnce(true);

    render(
      <SharePaymentHandleModal
        onDismiss={mockOnDismiss}
        paymentUrl={mockPaymentUrl}
        onPaymentShare={mockOnPaymentShare}
      />,
    );

    const amountInput = screen.getByLabelText('Enter amount (optional)');
    fireEvent.change(amountInput, { target: { value: '1000' } });

    const shareButton = screen.getByRole('button', { name: 'Copy Link' });
    fireEvent.click(shareButton);

    await waitFor(() => {
      expect(mockOnPaymentShare).toHaveBeenCalledWith({ amount: '1000' });
    });
  });

  it('renders mobile version correctly', () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    render(
      <SharePaymentHandleModal
        onDismiss={mockOnDismiss}
        paymentUrl={mockPaymentUrl}
        onPaymentShare={mockOnPaymentShare}
      />,
    );

    expect(screen.getByRole('button', { name: 'Share Link' })).toBeInTheDocument();
  });
});
