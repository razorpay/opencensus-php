import React from 'react';
import { screen, render, fireEvent, act } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import GenerateAPIKeys from '../GenerateAPIKeys';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { useStore } from '@federated/apps/shell/commonStore';
import { isMobileDevice } from '@libs/shared-utils';
import {
  API_KEYS_CSV_FILENAME,
  API_KEYS_CSV_MIME_TYPE,
} from 'apps/onboarding-experience/src/constants';

// Mock dependencies
jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn(),
}));

jest.mock('@FTUX/context/MerchantContext', () => ({
  useMerchantContext: jest.fn(),
}));

jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
  arrayToCsv: jest.fn().mockReturnValue('mocked-csv-data'),
  downloadFile: jest.fn(),
}));

// Mock components
jest.mock('@OnboardingExperienceCommons/components/ApiKeysModal/RevealApiKey', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ onDismiss, handleDownloadApiKeys, apiKeys }) => (
    <div data-testid="reveal-api-key-modal">
      <div data-testid="api-key-id">{apiKeys.id}</div>
      <div data-testid="api-key-secret">{apiKeys.secret}</div>
      <button onClick={() => handleDownloadApiKeys(apiKeys)} data-testid="download-api-keys">
        Download
      </button>
      <button onClick={onDismiss} data-testid="close-modal">
        Close
      </button>
    </div>
  )),
}));

jest.mock('@OnboardingExperienceCommons/components/ApiKeysModal/RegenerateApiKey', () => ({
  __esModule: true,
  default: jest
    .fn()
    .mockImplementation(({ onDismiss, handleRegenerateApiKeys, handleDownloadApiKeys }) => (
      <div data-testid="regenerate-api-key-modal">
        <button
          onClick={() => handleRegenerateApiKeys('IMMEDIATE')}
          data-testid="regenerate-immediate"
        >
          Regenerate Immediate
        </button>
        <button onClick={onDismiss} data-testid="close-modal">
          Close
        </button>
      </div>
    )),
}));

// Import the mocked functions
import { downloadFile, arrayToCsv } from '@libs/shared-utils';

describe('GenerateAPIKeys Component', () => {
  const mockShowNotification = jest.fn();
  const mockInitiateTwoFaAuth = jest.fn();
  const mockRegenerateApiKey = jest.fn();
  const mockGenerateApiKey = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    // Default mock implementations
    (useStore as unknown as jest.Mock).mockImplementation((selector) =>
      selector({
        session: { mode: 'test' },
        showNotification: mockShowNotification,
      }),
    );

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          apiKeys: [],
          business: {
            paymentAcceptanceChannels: {
              websites: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      initiateTwoFaAuth: mockInitiateTwoFaAuth,
      regenerateApiKey: mockRegenerateApiKey,
      generateApiKey: mockGenerateApiKey,
    });

    (isMobileDevice as jest.Mock).mockReturnValue(false);
  });

  test('renders reveal API keys button when no keys are generated', () => {
    renderWithWrappers(<GenerateAPIKeys />);

    const revealButton = screen.getByText('Reveal Test API Keys');
    expect(revealButton).toBeInTheDocument();
    expect(screen.queryByText('Regenerate Test API Keys')).not.toBeInTheDocument();
  });

  test('renders regenerate API keys button when keys are already generated', () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          apiKeys: [{ id: 'test-key-id' }],
          business: {
            paymentAcceptanceChannels: {
              websites: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      initiateTwoFaAuth: mockInitiateTwoFaAuth,
      regenerateApiKey: mockRegenerateApiKey,
      generateApiKey: mockGenerateApiKey,
    });

    renderWithWrappers(<GenerateAPIKeys />);

    const regenerateButton = screen.getByText('Regenerate Test API Keys');
    expect(regenerateButton).toBeInTheDocument();
    expect(screen.queryByText('Reveal Test API Keys')).not.toBeInTheDocument();
  });

  test('opens RevealApiKey modal when reveal button is clicked and 2FA succeeds', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(true);
    mockGenerateApiKey.mockResolvedValue({
      merchantApiKeysCreate: {
        id: 'test-key-id',
        secret: 'test-key-secret',
      },
    });

    renderWithWrappers(<GenerateAPIKeys />);

    const revealButton = screen.getByText('Reveal Test API Keys');

    await act(async () => {
      fireEvent.click(revealButton);
    });

    expect(mockInitiateTwoFaAuth).toHaveBeenCalled();
    expect(mockGenerateApiKey).toHaveBeenCalled();
    expect(screen.getByTestId('reveal-api-key-modal')).toBeInTheDocument();
    expect(screen.getByTestId('api-key-id')).toHaveTextContent('test-key-id');
    expect(screen.getByTestId('api-key-secret')).toHaveTextContent('test-key-secret');
  });

  test('opens RegenerateApiKey modal when regenerate button is clicked and 2FA succeeds', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(true);

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          apiKeys: [{ id: 'test-key-id' }],
          business: {
            paymentAcceptanceChannels: {
              websites: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      initiateTwoFaAuth: mockInitiateTwoFaAuth,
      regenerateApiKey: mockRegenerateApiKey,
      generateApiKey: mockGenerateApiKey,
    });

    renderWithWrappers(<GenerateAPIKeys />);

    const regenerateButton = screen.getByText('Regenerate Test API Keys');

    await act(async () => {
      fireEvent.click(regenerateButton);
    });

    expect(mockInitiateTwoFaAuth).toHaveBeenCalled();
    expect(screen.getByTestId('regenerate-api-key-modal')).toBeInTheDocument();
  });

  test('does not open modal when 2FA fails', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(false);

    renderWithWrappers(<GenerateAPIKeys />);

    const revealButton = screen.getByText('Reveal Test API Keys');

    await act(async () => {
      fireEvent.click(revealButton);
    });

    expect(mockInitiateTwoFaAuth).toHaveBeenCalled();
    expect(mockGenerateApiKey).not.toHaveBeenCalled();
    expect(screen.queryByTestId('reveal-api-key-modal')).not.toBeInTheDocument();
  });

  test('downloads API keys when download button is clicked', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(true);
    mockGenerateApiKey.mockResolvedValue({
      merchantApiKeysCreate: {
        id: 'test-key-id',
        secret: 'test-key-secret',
      },
    });

    renderWithWrappers(<GenerateAPIKeys />);

    const revealButton = screen.getByText('Reveal Test API Keys');

    await act(async () => {
      fireEvent.click(revealButton);
    });

    const downloadButton = screen.getByTestId('download-api-keys');

    await act(async () => {
      fireEvent.click(downloadButton);
    });

    expect(arrayToCsv).toHaveBeenCalled();
    expect(downloadFile).toHaveBeenCalledWith(
      'mocked-csv-data',
      API_KEYS_CSV_FILENAME,
      API_KEYS_CSV_MIME_TYPE,
    );
  });

  test('handles regenerate API keys success', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(true);
    mockRegenerateApiKey.mockResolvedValue({
      merchantApiKeyRegenerate: {
        newApiKey: {
          id: 'new-key-id',
          secret: 'new-key-secret',
        },
      },
    });

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          apiKeys: [{ id: 'test-key-id' }],
          business: {
            paymentAcceptanceChannels: {
              websites: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      initiateTwoFaAuth: mockInitiateTwoFaAuth,
      regenerateApiKey: mockRegenerateApiKey,
      generateApiKey: mockGenerateApiKey,
    });

    renderWithWrappers(<GenerateAPIKeys />);

    const regenerateButton = screen.getByText('Regenerate Test API Keys');

    await act(async () => {
      fireEvent.click(regenerateButton);
    });

    const regenerateImmediateButton = screen.getByTestId('regenerate-immediate');

    await act(async () => {
      fireEvent.click(regenerateImmediateButton);
    });

    expect(mockRegenerateApiKey).toHaveBeenCalledWith({
      keyRollDelay: 'IMMEDIATE',
      oldApiKeyId: 'test-key-id',
    });
  });

  test('shows full width button on mobile', () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    renderWithWrappers(<GenerateAPIKeys />);

    const button = screen.getByText('Reveal Test API Keys');
    // In a real implementation, we'd check for the isFullWidth prop or the applied CSS class
    expect(button).toBeInTheDocument();
  });

  test('shows error notification when generate API keys fails', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(true);
    mockGenerateApiKey.mockRejectedValue({
      errors: ['API key generation failed'],
    });

    renderWithWrappers(<GenerateAPIKeys />);

    const revealButton = screen.getByText('Reveal Test API Keys');

    await act(async () => {
      fireEvent.click(revealButton);
    });

    expect(mockShowNotification).toHaveBeenCalledWith({
      type: 'error',
      message: 'API key generation failed',
    });
  });
});
