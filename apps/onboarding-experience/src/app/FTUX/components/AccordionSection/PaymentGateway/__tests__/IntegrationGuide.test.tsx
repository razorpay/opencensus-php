import React from 'react';
import {
  fireEvent,
  screen,
  act,
  within,
  cleanup,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import IntegrationGuide from '../IntegrationGuide';
import { isMobileDevice } from '@libs/shared-utils';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';

// Mock dependencies
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
}));

jest.mock('@FTUX/context/MerchantContext', () => ({
  useMerchantContext: jest.fn(),
}));

jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn().mockImplementation((fn) => fn({ showNotification: jest.fn() })),
}));

// Mock SelectableOptionCard
jest.mock('@OnboardingExperienceCommons/components/SelectableOptionCard', () => ({
  __esModule: true,
  default: jest
    .fn()
    .mockImplementation(({ title, customTitle, subTitle, cardImageUrl, handleClick }) => (
      <div
        data-testid="selectable-option-card"
        onClick={handleClick}
        data-subtitle={subTitle}
        data-image-url={cardImageUrl}
      >
        {customTitle || <div data-testid="card-title">{title}</div>}
        {subTitle && <div data-testid="card-subtitle">{subTitle}</div>}
      </div>
    )),
}));

// Mock AppIntegrationGuide to isolate the tests
jest.mock('../AppIntegrationGuide', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ hasAndroidIntent, hasIOSIntent }) => (
    <div data-testid="app-integration-guide">
      <span data-testid="has-android">{String(hasAndroidIntent)}</span>
      <span data-testid="has-ios">{String(hasIOSIntent)}</span>
    </div>
  )),
}));

// Mock WebsitePluginModal
jest.mock('@OnboardingExperienceCommons/components/WebsitePluginModal', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ onDismiss, handleAddPlugin, supportedPlugins }) => (
    <div role="dialog" data-testid="website-plugin-modal">
      <button onClick={() => handleAddPlugin('Shopify')} data-testid="select-shopify">
        Select Shopify
      </button>
      <button onClick={onDismiss} data-testid="dismiss-modal">
        Close
      </button>
    </div>
  )),
}));

describe('IntegrationGuide Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Default mock implementation
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: null,
      onboardingData: null,
      addMerchantWebsitePlugin: jest.fn(),
    });
  });
  const mockAddMerchantWebsitePlugin = jest.fn();

  const defaultMerchantData = {
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
  };

  const defaultOnboardingData = {
    merchantOnboardingData: {
      supportedPlugins: [
        {
          name: 'Shopify',
          icon: 'shopify-icon-url',
          integrationGuide: 'https://shopify-guide.com',
        },
        {
          name: 'WooCommerce',
          icon: 'woocommerce-icon-url',
          integrationGuide: 'https://woocommerce-guide.com',
        },
      ],
      selectedPlugins: [],
    },
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false);
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: defaultMerchantData,
      onboardingData: defaultOnboardingData,
      addMerchantWebsitePlugin: mockAddMerchantWebsitePlugin,
    });
  });

  afterEach(() => {
    cleanup();
  });

  test('renders website integration options when website channel is available', async () => {
    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    expect(screen.getByText('What did you use to build your website?')).toBeInTheDocument();

    const cards = screen.getAllByTestId('selectable-option-card');
    expect(cards).toHaveLength(2);

    expect(screen.getByText('Custom website')).toBeInTheDocument();
    expect(screen.getByText('Used a web builder')).toBeInTheDocument();
  });

  test('shows selected plugin when plugin is already selected', async () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: defaultMerchantData,
      onboardingData: {
        merchantOnboardingData: {
          ...defaultOnboardingData.merchantOnboardingData,
          selectedPlugins: [
            {
              website: 'https://example.com',
              selectedPlugin: 'Shopify',
            },
          ],
        },
      },
      addMerchantWebsitePlugin: mockAddMerchantWebsitePlugin,
    });

    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    expect(screen.getByText('You selected Shopify')).toBeInTheDocument();
    expect(screen.getByText('Edit')).toBeInTheDocument();
    expect(screen.getByText('Here is a detailed set up guide')).toBeInTheDocument();
  });

  test('shows Custom Website when empty plugin is selected', async () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: defaultMerchantData,
      onboardingData: {
        merchantOnboardingData: {
          ...defaultOnboardingData.merchantOnboardingData,
          selectedPlugins: [
            {
              website: 'https://example.com',
              selectedPlugin: '',
            },
          ],
        },
      },
      addMerchantWebsitePlugin: mockAddMerchantWebsitePlugin,
    });

    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    expect(screen.getByText('You selected Custom Website')).toBeInTheDocument();
  });

  test('calls addMerchantWebsitePlugin when custom website option is clicked', async () => {
    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    const cards = screen.getAllByTestId('selectable-option-card');
    const customWebsiteCard = cards.find(
      (card) => within(card).queryByText('Custom website') !== null,
    );

    // Add non-null assertion to tell TypeScript this element exists
    expect(customWebsiteCard).not.toBeUndefined();

    await act(async () => {
      fireEvent.click(customWebsiteCard!);
    });

    expect(mockAddMerchantWebsitePlugin).toHaveBeenCalledWith({
      websiteUrl: 'https://example.com',
      pluginName: '',
    });
  });

  test('opens WebsitePluginModal when web builder option is clicked', async () => {
    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    const cards = screen.getAllByTestId('selectable-option-card');
    const webBuilderCard = cards.find(
      (card) => within(card).queryByText('Used a web builder') !== null,
    );

    // Add non-null assertion to tell TypeScript this element exists
    expect(webBuilderCard).not.toBeUndefined();

    await act(async () => {
      fireEvent.click(webBuilderCard!);
    });

    // Check if modal is displayed
    expect(screen.getByTestId('website-plugin-modal')).toBeInTheDocument();
  });

  test('selects plugin from modal and calls addMerchantWebsitePlugin', async () => {
    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    const cards = screen.getAllByTestId('selectable-option-card');
    const webBuilderCard = cards.find(
      (card) => within(card).queryByText('Used a web builder') !== null,
    );

    // Add non-null assertion to tell TypeScript this element exists
    expect(webBuilderCard).not.toBeUndefined();

    await act(async () => {
      fireEvent.click(webBuilderCard!);
    });

    const selectShopifyButton = screen.getByTestId('select-shopify');
    await act(async () => {
      fireEvent.click(selectShopifyButton);
    });

    expect(mockAddMerchantWebsitePlugin).toHaveBeenCalledWith({
      websiteUrl: 'https://example.com',
      pluginName: 'Shopify',
    });
  });

  test('closes modal when dismiss button is clicked', async () => {
    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    const cards = screen.getAllByTestId('selectable-option-card');
    const webBuilderCard = cards.find(
      (card) => within(card).queryByText('Used a web builder') !== null,
    );

    // Add non-null assertion to tell TypeScript this element exists
    expect(webBuilderCard).not.toBeUndefined();

    await act(async () => {
      fireEvent.click(webBuilderCard!);
    });

    expect(screen.getByTestId('website-plugin-modal')).toBeInTheDocument();

    const dismissButton = screen.getByTestId('dismiss-modal');
    await act(async () => {
      fireEvent.click(dismissButton);
    });

    expect(screen.queryByTestId('website-plugin-modal')).not.toBeInTheDocument();
  });

  test('does not add plugin if API key access is not available', async () => {
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
      onboardingData: defaultOnboardingData,
      addMerchantWebsitePlugin: mockAddMerchantWebsitePlugin,
    });

    await act(async () => {
      renderWithWrappers(<IntegrationGuide />);
    });

    const cards = screen.getAllByTestId('selectable-option-card');
    const customWebsiteCard = cards.find(
      (card) => within(card).queryByText('Custom website') !== null,
    );

    // Add non-null assertion to tell TypeScript this element exists
    expect(customWebsiteCard).not.toBeUndefined();

    await act(async () => {
      fireEvent.click(customWebsiteCard!);
    });

    expect(mockAddMerchantWebsitePlugin).not.toHaveBeenCalled();
  });
});
