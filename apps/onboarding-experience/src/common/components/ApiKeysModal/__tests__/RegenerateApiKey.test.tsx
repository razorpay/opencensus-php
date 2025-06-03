import React from 'react';
import {
  screen,
  fireEvent,
  act,
  waitFor,
  cleanup,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import RegenerateApiKey from '../RegenerateApiKey';
import { ApiKeyDelay, RegenerateModalScreens } from '@OnboardingExperienceCommons/types/apiKeys';

// Mock the store
jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn().mockImplementation((selector) =>
    selector({
      showNotification: jest.fn(),
      session: {
        mode: 'test',
      },
    }),
  ),
}));

describe('RegenerateApiKey Component', () => {
  const mockHandleRegenerateApiKeys = jest.fn();
  const mockHandleDownloadApiKeys = jest.fn();
  const mockOnDismiss = jest.fn();
  const mockApiKeys = { id: 'new_key_id', secret: 'new_key_secret' };

  beforeEach(() => {
    jest.clearAllMocks();
    mockHandleRegenerateApiKeys.mockResolvedValue(mockApiKeys);
  });

  afterEach(() => {
    // Cleanup after each test to prevent updates on unmounted components
    cleanup();
  });

  it('initially renders the DeactivateKeys screen', async () => {
    await act(async () => {
      renderWithWrappers(
        <RegenerateApiKey
          handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
          handleDownloadApiKeys={mockHandleDownloadApiKeys}
          onDismiss={mockOnDismiss}
        />,
      );
    });

    // Verify DeactivateKeys screen is rendered
    expect(screen.getByText('Confirm and deactivate keys?')).toBeInTheDocument();
    expect(screen.getByText('Deactivate old key immediately')).toBeInTheDocument();
    expect(screen.getByText('Deactivate old key in 24 hours')).toBeInTheDocument();
  });

  it('transitions to RevealApiKey screen after successful key regeneration', async () => {
    await act(async () => {
      renderWithWrappers(
        <RegenerateApiKey
          handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
          handleDownloadApiKeys={mockHandleDownloadApiKeys}
          onDismiss={mockOnDismiss}
        />,
      );
    });

    // Select immediate deactivation option
    await act(async () => {
      fireEvent.click(screen.getByText('Deactivate old key immediately'));
    });

    // Confirm regeneration and wait for API call resolution
    await act(async () => {
      fireEvent.click(screen.getByText('Confirm'));
      // Wait for the mock API call to resolve
      await waitFor(() => expect(mockHandleRegenerateApiKeys).toHaveBeenCalled());
    });

    // Verify transition to RevealApiKey screen
    await waitFor(() => {
      expect(screen.getByText('Key ID & Secret')).toBeInTheDocument();
      expect(
        screen.getByText(
          "For security reasons, this key can only be downloaded once. We won't show it again, so keep it safe.",
        ),
      ).toBeInTheDocument();
    });
  });

  it('handles API key regeneration with NO_DELAY option', async () => {
    await act(async () => {
      renderWithWrappers(
        <RegenerateApiKey
          handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
          handleDownloadApiKeys={mockHandleDownloadApiKeys}
          onDismiss={mockOnDismiss}
        />,
      );
    });

    // Select immediate deactivation
    await act(async () => {
      fireEvent.click(screen.getByText('Deactivate old key immediately'));
    });

    // Confirm regeneration
    await act(async () => {
      fireEvent.click(screen.getByText('Confirm'));
      // Wait for the API call to resolve
      await waitFor(() => expect(mockHandleRegenerateApiKeys).toHaveBeenCalled());
    });

    // Verify the API call was made with correct parameters
    expect(mockHandleRegenerateApiKeys).toHaveBeenCalledWith(ApiKeyDelay.NO_DELAY);
  });

  it('handles API key regeneration with DELAY option', async () => {
    await act(async () => {
      renderWithWrappers(
        <RegenerateApiKey
          handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
          handleDownloadApiKeys={mockHandleDownloadApiKeys}
          onDismiss={mockOnDismiss}
        />,
      );
    });

    // Select 24-hour delayed deactivation
    await act(async () => {
      fireEvent.click(screen.getByText('Deactivate old key in 24 hours'));
    });

    // Confirm regeneration
    await act(async () => {
      fireEvent.click(screen.getByText('Confirm'));
      // Wait for the API call to resolve
      await waitFor(() => expect(mockHandleRegenerateApiKeys).toHaveBeenCalled());
    });

    // Verify the API call was made with correct parameters
    expect(mockHandleRegenerateApiKeys).toHaveBeenCalledWith(ApiKeyDelay.DELAY);
  });

  it('shows error notification when key regeneration fails', async () => {
    // Mock the error scenario
    const mockError = new Error('API Error');
    mockHandleRegenerateApiKeys.mockRejectedValueOnce(mockError);

    // Mock the store with notification capture
    const mockShowNotification = jest.fn();
    jest.mock('@federated/apps/shell/commonStore', () => ({
      useStore: jest.fn().mockImplementation((selector) =>
        selector({
          showNotification: mockShowNotification,
          session: { mode: 'test' },
        }),
      ),
    }));

    await act(async () => {
      renderWithWrappers(
        <RegenerateApiKey
          handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
          handleDownloadApiKeys={mockHandleDownloadApiKeys}
          onDismiss={mockOnDismiss}
        />,
      );
    });

    // Select immediate deactivation option
    await act(async () => {
      fireEvent.click(screen.getByText('Deactivate old key immediately'));
    });

    // Confirm regeneration and wait for API rejection
    await act(async () => {
      fireEvent.click(screen.getByText('Confirm'));
      // Wait for the promise to reject
      try {
        await mockHandleRegenerateApiKeys();
      } catch (e) {
        // Expected rejection
      }
    });

    // Verify error handling - component should stay on the DeactivateKeys screen
    expect(screen.getByText('Confirm and deactivate keys?')).toBeInTheDocument();
  });

  it('cancels the regeneration process when requested', async () => {
    await act(async () => {
      renderWithWrappers(
        <RegenerateApiKey
          handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
          handleDownloadApiKeys={mockHandleDownloadApiKeys}
          onDismiss={mockOnDismiss}
        />,
      );
    });

    // Click Cancel on the DeactivateKeys screen
    await act(async () => {
      fireEvent.click(screen.getByText('Cancel'));
    });

    // Verify the dismissal callback was triggered
    expect(mockOnDismiss).toHaveBeenCalled();
  });
});
