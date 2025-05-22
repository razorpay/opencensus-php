import React from 'react';
import {
  screen,
  fireEvent,
  act,
  waitFor,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import RevealApiKey from '../RevealApiKey';
import { copyToClipboard, isMobileDevice } from '@libs/shared-utils';

// Mock shared dependencies
jest.mock('@libs/shared-utils', () => ({
  copyToClipboard: jest.fn(),
  isMobileDevice: jest.fn().mockReturnValue(false),
}));

// Mock the store
const mockShowNotification = jest.fn();
jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn().mockImplementation((selector) =>
    selector({
      showNotification: mockShowNotification,
      session: {
        mode: 'test',
      },
    }),
  ),
}));

describe('RevealApiKey Component', () => {
  const mockHandleDownloadApiKeys = jest.fn();
  const mockOnDismiss = jest.fn();
  const apiKeys = {
    id: 'test_key_id',
    secret: 'test_key_secret',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockHandleDownloadApiKeys.mockResolvedValue(undefined);
  });

  it('renders with test environment key labels when in test mode', () => {
    renderWithWrappers(
      <RevealApiKey
        apiKeys={apiKeys}
        handleDownloadApiKeys={mockHandleDownloadApiKeys}
        onDismiss={mockOnDismiss}
        isFetching={false}
      />,
    );
    expect(screen.getByText('Key ID & Secret')).toBeInTheDocument();
    expect(screen.getByTestId('api-keys-modal-key-id')).toBeInTheDocument();
    expect(screen.getByTestId('api-keys-modal-key-secret')).toBeInTheDocument();
    expect(screen.getByText('Test Key ID')).toBeInTheDocument();
    expect(screen.getByText('Test Key Secret')).toBeInTheDocument();
    expect(screen.getByText('Download')).toBeInTheDocument();
    expect(
      screen.getByText(
        "For security reasons, this key can only be downloaded once. We won't show it again, so keep it safe.",
      ),
    ).toBeInTheDocument();
  });

  it('triggers copy to clipboard when copy icon is clicked', async () => {
    renderWithWrappers(
      <RevealApiKey
        apiKeys={apiKeys}
        handleDownloadApiKeys={mockHandleDownloadApiKeys}
        onDismiss={mockOnDismiss}
        isFetching={false}
      />,
    );

    // Find all copy icons (there should be two: one for ID, one for secret)
    const copyButtons = screen.getAllByRole('link');
    expect(copyButtons).toHaveLength(2);

    // Click on the Key ID copy button
    fireEvent.click(copyButtons[0]);
    expect(copyToClipboard).toHaveBeenCalledWith(apiKeys.id);

    // Click on the Key Secret copy button
    fireEvent.click(copyButtons[1]);
    expect(copyToClipboard).toHaveBeenCalledWith(apiKeys.secret);
  });

  it('initiates download when download button is clicked', async () => {
    renderWithWrappers(
      <RevealApiKey
        apiKeys={apiKeys}
        handleDownloadApiKeys={mockHandleDownloadApiKeys}
        onDismiss={mockOnDismiss}
        isFetching={false}
      />,
    );

    // Click download button
    await act(async () => {
      fireEvent.click(screen.getByText('Download'));
    });

    // Verify download was initiated
    expect(mockHandleDownloadApiKeys).toHaveBeenCalledWith(apiKeys);
  });

  it('changes button state to "Downloaded" after successful download', async () => {
    renderWithWrappers(
      <RevealApiKey
        apiKeys={apiKeys}
        handleDownloadApiKeys={mockHandleDownloadApiKeys}
        onDismiss={mockOnDismiss}
        isFetching={false}
      />,
    );

    // Click download button
    await act(async () => {
      fireEvent.click(screen.getByText('Download'));
    });

    // Wait for the download to complete
    await waitFor(() => {
      expect(screen.getByText('Downloaded')).toBeInTheDocument();
    });
  });

  it('shows spinner during fetching state', () => {
    renderWithWrappers(
      <RevealApiKey
        apiKeys={apiKeys}
        handleDownloadApiKeys={mockHandleDownloadApiKeys}
        onDismiss={mockOnDismiss}
        isFetching={true}
      />,
    );

    // Check for spinners
    const spinners = screen.getAllByLabelText('api-keys-spinner');
    expect(spinners).toHaveLength(2);
  });

  it('handles download errors gracefully with notification', async () => {
    // Mock the error scenario
    const mockError = new Error('Download failed');
    mockHandleDownloadApiKeys.mockRejectedValueOnce(mockError);

    renderWithWrappers(
      <RevealApiKey
        apiKeys={apiKeys}
        handleDownloadApiKeys={mockHandleDownloadApiKeys}
        onDismiss={mockOnDismiss}
        isFetching={false}
      />,
    );

    // Click download button
    await act(async () => {
      fireEvent.click(screen.getByText('Download'));
    });

    // Verify error handling process
    expect(mockHandleDownloadApiKeys).toHaveBeenCalledWith(apiKeys);

    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'Download failed',
      });
    });

    // Button should not show "Downloaded" state after error
    expect(screen.getByText('Download')).toBeInTheDocument();
  });

  it('shows generic error message when error has no message', async () => {
    // Mock the error scenario with no message
    mockHandleDownloadApiKeys.mockRejectedValueOnce(new Error());

    renderWithWrappers(
      <RevealApiKey
        apiKeys={apiKeys}
        handleDownloadApiKeys={mockHandleDownloadApiKeys}
        onDismiss={mockOnDismiss}
        isFetching={false}
      />,
    );

    // Click download button
    await act(async () => {
      fireEvent.click(screen.getByText('Download'));
    });

    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'Unable to Download Api Keys! - Please try again later',
      });
    });
  });

  describe('Live mode', () => {
    beforeEach(() => {
      // Override the default mock to use live mode
      jest.mock('@federated/apps/shell/commonStore', () => ({
        useStore: jest.fn().mockImplementation((selector) =>
          selector({
            showNotification: mockShowNotification,
            session: { mode: 'live' },
          }),
        ),
      }));
    });

    it('displays live environment key labels when in live mode', () => {
      // Mock useStore explicitly for this test
      const useStoreMock = require('@federated/apps/shell/commonStore').useStore;
      useStoreMock.mockImplementation((selector: (state: any) => any) =>
        selector({
          showNotification: mockShowNotification,
          session: { mode: 'live' },
        }),
      );

      renderWithWrappers(
        <RevealApiKey
          apiKeys={apiKeys}
          handleDownloadApiKeys={mockHandleDownloadApiKeys}
          onDismiss={mockOnDismiss}
          isFetching={false}
        />,
      );

      expect(screen.getByText('Live Key ID')).toBeInTheDocument();
      expect(screen.getByText('Live Key Secret')).toBeInTheDocument();
    });
  });
});
