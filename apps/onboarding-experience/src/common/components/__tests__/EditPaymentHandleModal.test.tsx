import React from 'react';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import EditPaymentHandleModal from '../EditPaymentHandleModal';
import { act } from 'react-dom/test-utils';

jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn().mockImplementation((selector) =>
    selector({
      showNotification: jest.fn(),
    }),
  ),
}));

describe('EditPaymentHandleModal', () => {
  const mockOnDismiss = jest.fn();
  const mockGetHandleSuggestions = jest
    .fn()
    .mockResolvedValue(['suggestion1', 'suggestion2', 'suggestion3']);
  const mockGetPaymentHandleAvailability = jest.fn().mockResolvedValue(true);
  const mockHandleUpdatePaymentHandle = jest.fn().mockResolvedValue(undefined);
  const mockShowNotification = jest.fn();

  const defaultProps = {
    onDismiss: mockOnDismiss,
    getHandleSuggestions: mockGetHandleSuggestions,
    getPaymentHandleAvailability: mockGetPaymentHandleAvailability,
    handleUpdatePaymentHandle: mockHandleUpdatePaymentHandle,
    currentPaymentHandle: '@current-handle',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(console, 'error').mockImplementation(() => {});

    // Mock useStore to return showNotification
    require('@federated/apps/shell/commonStore').useStore.mockImplementation((selector: any) =>
      selector({ showNotification: mockShowNotification }),
    );
  });

  it('renders modal with correct title and subtitle', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    expect(screen.getByText('Edit your Razorpay.me link')).toBeInTheDocument();
    expect(screen.getByText('Change your handle to whatever you like')).toBeInTheDocument();
  });

  it('initializes with the current payment handle', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');
    expect(input).toHaveValue('current-handle');
  });

  it('checks handle availability when input changes', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    // Clear and type a new handle
    await act(async () => {
      fireEvent.change(input, { target: { value: 'new-handle' } });
    });

    // Wait for debounce to complete
    await waitFor(() => {
      expect(mockGetPaymentHandleAvailability).toHaveBeenCalledWith('new-handle');
    });
  });

  it('shows success message for available handle', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue(true);

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: 'available-handle' } });
    });

    // Wait for debounce to complete
    await waitFor(() => {
      expect(screen.getByText('This handle is available!')).toBeInTheDocument();
    });
  });

  it('shows error message for unavailable handle', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue(false);

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: 'unavailable-handle' } });
    });

    // Wait for debounce to complete
    await waitFor(() => {
      expect(screen.getByText('This handle is not available')).toBeInTheDocument();
    });
  });

  it('shows error for handles less than 3 characters', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: 'ab' } });
    });

    await waitFor(() => {
      expect(screen.getByText('Handle should have atleast 3 characters!')).toBeInTheDocument();
    });
  });

  it('loads suggestions on component mount', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    await waitFor(() => {
      expect(mockGetHandleSuggestions).toHaveBeenCalled();
    });

    // Use a more flexible way to find text that might be broken up
    await waitFor(() => {
      const suggestionText = screen.getByText(/suggestions are/i);
      expect(suggestionText).toBeInTheDocument();
    });

    // Check individual suggestions
    expect(screen.getByText(/suggestion1/)).toBeInTheDocument();
    expect(screen.getByText(/suggestion2/)).toBeInTheDocument();
    expect(screen.getByText(/suggestion3/)).toBeInTheDocument();
  });

  it('allows selecting a suggestion', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    await waitFor(() => {
      expect(mockGetHandleSuggestions).toHaveBeenCalled();
    });

    // Click on a suggestion, using more flexible text matching
    await act(async () => {
      const suggestion = screen.getByText(/suggestion1/);
      fireEvent.click(suggestion);
    });

    const input = screen.getByLabelText('Your handle');
    expect(input).toHaveValue('suggestion1');
  });

  it('disables Save button when handle is invalid or unavailable', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue(false);

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: 'unavailable-handle' } });
    });

    // More reliable button selection
    await waitFor(() => {
      const saveButton = screen.getByRole('button', { name: /save changes/i });
      expect(saveButton).toBeDisabled();
    });
  });

  it('updates payment handle when Save is clicked', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue(true);

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: 'new-valid-handle' } });
    });

    await waitFor(() => {
      const saveButton = screen.getByRole('button', { name: /save changes/i });
      expect(saveButton).not.toBeDisabled();
    });

    // Click Save button using role
    fireEvent.click(screen.getByRole('button', { name: /save changes/i }));

    // Check if the update function was called
    await waitFor(() => {
      expect(mockHandleUpdatePaymentHandle).toHaveBeenCalledWith('new-valid-handle');
    });

    // Verify notification was shown
    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'success',
        message: 'Payment handle updated successfully!',
      });
    });

    // Verify modal was dismissed
    expect(mockOnDismiss).toHaveBeenCalled();
  });

  it('calls onDismiss when Cancel is clicked', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    // Click Cancel button using role instead of text
    fireEvent.click(screen.getByRole('button', { name: /cancel/i }));
    expect(mockOnDismiss).toHaveBeenCalled();
  });
});
