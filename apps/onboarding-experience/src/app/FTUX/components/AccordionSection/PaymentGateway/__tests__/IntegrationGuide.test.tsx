import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import userEvent from '@testing-library/user-event';
import IntegrationGuide from '../IntegrationGuide';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';

// Mock the MerchantContext hook
jest.mock('@FTUX/context/MerchantContext');

// Mock the window.open function
const mockOpen = jest.fn();
window.open = mockOpen;

describe('IntegrationGuide Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Default mock implementation
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: null,
      onboardingData: null,
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });
  });

  test('renders website integration options when website URL is available', () => {
    // Mock merchant data with a website URL
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Websites]: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          supportedPlugins: [
            {
              name: 'Shopify',
              icon: 'shopify-icon.svg',
              integrationGuide: 'https://shopify-guide.com',
            },
            {
              name: 'WooCommerce',
              icon: 'woo-icon.svg',
              integrationGuide: 'https://woo-guide.com',
            },
          ],
        },
      },
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });

    renderWithWrappers(<IntegrationGuide />);

    // Should show website integration options
    expect(screen.getByText('What did you use to build your website?')).toBeInTheDocument();
    expect(screen.getByText('Custom website')).toBeInTheDocument();
    expect(screen.getByText('Used a web builder')).toBeInTheDocument();
  });

  test('renders website integration with selected plugin', async () => {
    // Mock merchant data with a website URL and a selected plugin
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Websites]: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          supportedPlugins: [
            {
              name: 'Shopify',
              icon: 'shopify-icon.svg',
              integrationGuide: 'https://shopify-guide.com',
            },
          ],
          selectedPlugins: [{ website: 'https://example.com', selectedPlugin: 'Shopify' }],
        },
      },
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });

    renderWithWrappers(<IntegrationGuide />);

    // Should show the selected plugin with integration guide
    expect(screen.getByText(/Build on Shopify/)).toBeInTheDocument();
    expect(screen.getByText('- step-by-step guide to set up')).toBeInTheDocument();

    // Should have an Edit link
    expect(screen.getByText('Edit')).toBeInTheDocument();
  });

  test('renders Android integration option when Android channel is enabled', () => {
    // Mock merchant data with Android channel enabled
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Websites]: {
                urls: [{ value: 'https://example.com' }],
              },
              [PAYMENT_CHANNEL_OPTIONS.Android]: {
                accept: true,
              },
            },
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {},
      },
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });

    renderWithWrappers(<IntegrationGuide />);

    // Should show Android integration resource
    expect(screen.getByText('Resources for Apps')).toBeInTheDocument();
    expect(screen.getByText('Build API Integration on Android')).toBeInTheDocument();
  });

  test('renders iOS integration option when iOS channel is enabled', () => {
    // Mock merchant data with iOS channel enabled
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Websites]: {
                urls: [{ value: 'https://example.com' }],
              },
              [PAYMENT_CHANNEL_OPTIONS.IOS]: {
                accept: true,
              },
            },
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {},
      },
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });

    renderWithWrappers(<IntegrationGuide />);

    // Should show iOS integration resource
    expect(screen.getByText('Resources for Apps')).toBeInTheDocument();
    expect(screen.getByText('Build API Integration on iOS')).toBeInTheDocument();
  });

  test('shows app integration options when website is not verified', () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: false,
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Websites]: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          supportedPlugins: [],
        },
      },
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });

    renderWithWrappers(<IntegrationGuide />);

    // Should show app integration options even without Android/iOS channels
    expect(screen.getByText('Resources for Apps')).toBeInTheDocument();
    expect(screen.getByText('Build API Integration on Android')).toBeInTheDocument();
    expect(screen.getByText('Build API Integration on iOS')).toBeInTheDocument();
  });

  test('shows app integration options when API key access is not available', () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: false,
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Websites]: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          supportedPlugins: [],
          selectedPlugins: [{ website: 'https://example.com', selectedPlugin: 'Custom Website' }],
        },
      },
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });

    renderWithWrappers(<IntegrationGuide />);

    // With no API key access, will see both Android and iOS links
    expect(screen.getByText('Resources for Apps')).toBeInTheDocument();
    expect(screen.getByText('Build API Integration on Android')).toBeInTheDocument();
    expect(screen.getByText('Build API Integration on iOS')).toBeInTheDocument();
  });

  test('resets selected plugin when clicking on Edit button', async () => {
    const user = userEvent.setup();

    // Mock merchant data with a website URL and a selected plugin
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          hasApiKeyAccess: true,
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Websites]: {
                urls: [{ value: 'https://example.com' }],
              },
            },
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          supportedPlugins: [
            {
              name: 'Shopify',
              icon: 'shopify-icon.svg',
              integrationGuide: 'https://shopify-guide.com',
            },
          ],
          selectedPlugins: [{ website: 'https://example.com', selectedPlugin: 'Shopify' }],
        },
      },
      addMerchantWebsitePlugin: jest.fn(),
      isAddingWebsitePlugin: false,
    });

    renderWithWrappers(<IntegrationGuide />);

    // Verify selected plugin information is shown
    expect(screen.getByText(/Build on Shopify/)).toBeInTheDocument();

    // Click the Edit button
    await user.click(screen.getByText('Edit'));

    // The website integration options should be shown again
    await waitFor(() => {
      expect(screen.getByText('Custom website')).toBeInTheDocument();
      expect(screen.getByText('Used a web builder')).toBeInTheDocument();
    });
  });
});
