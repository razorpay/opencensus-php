import React from 'react';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import SharePaymentHandleModal from '../SharePaymentHandleModal';
import { act } from 'react-dom/test-utils';
import { isMobileDevice } from '@libs/shared-utils';

// Mock only what's absolutely necessary
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn().mockReturnValue(false),
}));

jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn().mockImplementation((selector) =>
    selector({
      showNotification: jest.fn(),
    }),
  ),
}));

describe('SharePaymentHandleModal', () => {
  const mockOnDismiss = jest.fn();
  const mockOnPaymentShare = jest.fn();
  const mockShowNotification = jest.fn();
  const mockPaymentUrl = 'https://example.com/pay';

  beforeEach(() => {
    jest.clearAllMocks();

    // Mock useStore to return showNotification
    require('@federated/apps/shell/commonStore').useStore.mockImplementation((selector: any) =>
      selector({ showNotification: mockShowNotification }),
    );

    // Default to desktop view
    (isMobileDevice as jest.Mock).mockReturnValue(false);

    // Use fake timers for setTimeout tests
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  it('renders correctly with all props', async () => {
    await act(async () => {
      renderWithWrappers(
        <SharePaymentHandleModal
          onDismiss={mockOnDismiss}
          paymentUrl={mockPaymentUrl}
          onPaymentShare={mockOnPaymentShare}
        />,
      );
    });

    expect(screen.getByText('Share Payment Handle')).toBeInTheDocument();
    expect(screen.getByText('Enter amount and share')).toBeInTheDocument();
    expect(screen.getByText('Your handle')).toBeInTheDocument();
    expect(screen.getByLabelText('Enter amount (optional)')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Copy Link/i })).toBeInTheDocument();
  });

  it('handles amount input correctly', async () => {
    await act(async () => {
      renderWithWrappers(
        <SharePaymentHandleModal
          onDismiss={mockOnDismiss}
          paymentUrl={mockPaymentUrl}
          onPaymentShare={mockOnPaymentShare}
        />,
      );
    });

    const amountInput = screen.getByLabelText('Enter amount (optional)');

    await act(async () => {
      fireEvent.change(amountInput, { target: { value: '1000' } });
    });

    expect(amountInput).toHaveValue('1000');
  });

  it('handles share with amount correctly', async () => {
    mockOnPaymentShare.mockResolvedValueOnce(true);

    await act(async () => {
      renderWithWrappers(
        <SharePaymentHandleModal
          onDismiss={mockOnDismiss}
          paymentUrl={mockPaymentUrl}
          onPaymentShare={mockOnPaymentShare}
        />,
      );
    });

    const amountInput = screen.getByLabelText('Enter amount (optional)');

    await act(async () => {
      fireEvent.change(amountInput, { target: { value: '1000' } });
    });

    const shareButton = screen.getByRole('button', { name: /Copy Link/i });

    await act(async () => {
      fireEvent.click(shareButton);
    });

    await waitFor(() => {
      expect(mockOnPaymentShare).toHaveBeenCalledWith({ amount: '1000' });
    });
  });

  it('renders mobile version correctly', async () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    await act(async () => {
      renderWithWrappers(
        <SharePaymentHandleModal
          onDismiss={mockOnDismiss}
          paymentUrl={mockPaymentUrl}
          onPaymentShare={mockOnPaymentShare}
        />,
      );
    });

    expect(screen.getByRole('button', { name: 'Share Link' })).toBeInTheDocument();
  });

  it('shows error notification when share fails', async () => {
    const errorMessage = 'Failed to share payment link';
    mockOnPaymentShare.mockRejectedValueOnce(new Error(errorMessage));

    await act(async () => {
      renderWithWrappers(
        <SharePaymentHandleModal
          onDismiss={mockOnDismiss}
          paymentUrl={mockPaymentUrl}
          onPaymentShare={mockOnPaymentShare}
        />,
      );
    });

    const shareButton = screen.getByRole('button', { name: /Copy Link/i });

    await act(async () => {
      fireEvent.click(shareButton);
    });

    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'error',
        content: errorMessage,
      });
    });
  });
});
