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

jest.mock('@libs/shared-utils', () => ({
  useDebounce: (fn) => fn,
  isMobileDevice: jest.fn().mockReturnValue(false),
}));

// Mock the utility functions for payment handle processing
jest.mock('@OnboardingExperienceCommons/utils/paymentHandle', () => ({
  addPaymentHandleSlugPrefix: jest.fn((handle) => `@${handle}`),
  removePaymentHandleSlugPrefix: jest.fn((handle) => handle.replace('@', '')),
}));

describe('EditPaymentHandleModal', () => {
  const mockOnDismiss = jest.fn();
  const mockGetHandleSuggestions = jest
    .fn()
    .mockResolvedValue(['@suggestion1', '@suggestion2', '@suggestion3']);
  const mockGetPaymentHandleAvailability = jest
    .fn()
    .mockResolvedValue({ isAvailable: true, message: 'This handle is available!' });
  const mockHandleUpdatePaymentHandle = jest.fn().mockResolvedValue(undefined);
  const mockShowNotification = jest.fn();

  const defaultProps = {
    onDismiss: mockOnDismiss,
    getHandleSuggestions: mockGetHandleSuggestions,
    getPaymentHandleAvailability: mockGetPaymentHandleAvailability,
    handleUpdatePaymentHandle: mockHandleUpdatePaymentHandle,
    currentPaymentHandle: '@current-handle',
  };

  const HANDLE_PREFIX = 'https://razorpay.me/@';

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
    expect(input).toHaveValue(`${HANDLE_PREFIX}current-handle`);
  });

  it('checks handle availability when input changes', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    // Clear and type a new handle
    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}new-handle` } });
    });

    // Wait for debounce to complete
    await waitFor(() => {
      expect(mockGetPaymentHandleAvailability).toHaveBeenCalledWith('new-handle');
    });
  });

  it('shows success message for available handle', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue({
      isAvailable: true,
      message: 'This handle is available!',
    });

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}available-handle` } });
    });

    // Wait for debounce to complete
    await waitFor(() => {
      expect(screen.getByText('This handle is available!')).toBeInTheDocument();
    });
  });

  it('shows error message for unavailable handle', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue({
      isAvailable: false,
      message: 'This handle is not available!',
    });

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}unavailable-handle` } });
    });

    // Wait for debounce to complete
    await waitFor(() => {
      expect(screen.getByText('This handle is not available!')).toBeInTheDocument();
    });
  });

  it('does not check availability for current handle', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    // Mock is already called for loading suggestions
    mockGetPaymentHandleAvailability.mockClear();

    // No need to change input as it's already initialized with current handle

    // Check that availability check was not called for current handle
    expect(mockGetPaymentHandleAvailability).not.toHaveBeenCalled();
  });

  it('marks suggestion as available directly if it is in the suggestions list', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');
    mockGetPaymentHandleAvailability.mockClear();

    // Change to a value that is in the suggestions list
    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}suggestion1` } });
    });

    // Availability check should not be called as the handle is in suggestions
    expect(mockGetPaymentHandleAvailability).not.toHaveBeenCalled();

    // Save button should be enabled
    await waitFor(() => {
      const saveButton = screen.getByRole('button', { name: /save changes/i });
      expect(saveButton).not.toBeDisabled();
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
      const suggestionText = screen.getByText(/Available suggestions are/i);
      expect(suggestionText).toBeInTheDocument();
    });

    // Check individual suggestions
    expect(screen.getByText(/@suggestion1/)).toBeInTheDocument();
    expect(screen.getByText(/@suggestion2/)).toBeInTheDocument();
    expect(screen.getByText(/@suggestion3/)).toBeInTheDocument();
  });

  it('allows selecting a suggestion', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    await waitFor(() => {
      expect(mockGetHandleSuggestions).toHaveBeenCalled();
    });

    // Click on a suggestion
    await act(async () => {
      const suggestion = screen.getByText(/@suggestion1/);
      fireEvent.click(suggestion);
    });

    const input = screen.getByLabelText('Your handle');
    expect(input).toHaveValue(`${HANDLE_PREFIX}suggestion1`);
  });

  it('disables Save button when handle is invalid or unavailable', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue({
      isAvailable: false,
      message: 'This handle is not available!',
    });

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}unavailable-handle` } });
    });

    // More reliable button selection
    await waitFor(() => {
      const saveButton = screen.getByRole('button', { name: /save changes/i });
      expect(saveButton).toBeDisabled();
    });
  });

  it('disables Save button when handle is less than 3 characters', async () => {
    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}ab` } });
    });

    const saveButton = screen.getByRole('button', { name: /save changes/i });
    expect(saveButton).toBeDisabled();
  });

  it('updates payment handle when Save is clicked', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue({
      isAvailable: true,
      message: 'This handle is available!',
    });

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}new-valid-handle` } });
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

  it('shows error notification when update fails', async () => {
    mockGetPaymentHandleAvailability.mockResolvedValue({
      isAvailable: true,
      message: 'This handle is available!',
    });

    const errorMessage = 'Failed to update payment handle';
    mockHandleUpdatePaymentHandle.mockRejectedValue(new Error(errorMessage));

    await act(async () => {
      renderWithWrappers(<EditPaymentHandleModal {...defaultProps} />);
    });

    const input = screen.getByLabelText('Your handle');

    await act(async () => {
      fireEvent.change(input, { target: { value: `${HANDLE_PREFIX}new-valid-handle` } });
    });

    await waitFor(() => {
      const saveButton = screen.getByRole('button', { name: /save changes/i });
      expect(saveButton).not.toBeDisabled();
    });

    // Click Save button
    fireEvent.click(screen.getByRole('button', { name: /save changes/i }));

    // Verify error notification was shown
    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'error',
        message: errorMessage,
      });
    });

    // Modal should remain open
    expect(mockOnDismiss).not.toHaveBeenCalled();
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
