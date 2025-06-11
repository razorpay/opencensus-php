import React from 'react';
import {
  screen,
  fireEvent,
  waitFor,
  within,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import AddWebsiteNudge, { PlatformType } from '../index';
import { WebsiteSubmitModalSteps } from '@federated/dashboards/payments/types/websites';

// Mock necessary icons
jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  BankAccountVerificationIcon: jest.fn(),
  ArrowUpRightIcon: jest.fn(),
}));

// Mock isMobileDevice
jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  isMobileDevice: jest.fn().mockReturnValue(false),
  scrollTo: jest.fn(),
}));

// Mock image imports
jest.mock(
  '@OnboardingExperienceAssets/NoCodeProducts/AddWebsiteCardMWeb.svg',
  () => 'mock-website-card-mweb-path',
);
jest.mock(
  '@OnboardingExperienceAssets/NoCodeProducts/AddWebsiteCardThumbnail.svg',
  () => 'mock-website-card-thumbnail-path',
);

// Mock the PitchProducts component
jest.mock('@FTUX/components/PitchProducts', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ title, products }) => (
    <div data-testid="pitch-products">
      <h2>{title}</h2>
      <div data-testid="products-list">
        {products.map((product: any, index: number) => (
          <div key={index} data-testid={`product-${index}`}>
            <div>{product.tagText}</div>
            <div>{product.title}</div>
            <div>{product.description}</div>
            <button
              data-testid={`product-${index}-link`}
              onClick={product.handleClick}
              disabled={product.isCtaLoading}
            >
              {product.linkText}
            </button>
          </div>
        ))}
      </div>
    </div>
  )),
}));

// Mock the WebsiteV2Modal component
jest.mock('@federated/dashboards/payments/components/WebsiteModalsWrapper', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ onDismiss, refreshWebsiteData }) => (
    <div data-testid="website-v2-modal">
      <button data-testid="dismiss-button" onClick={onDismiss}>
        Close
      </button>
      <button data-testid="refresh-button" onClick={refreshWebsiteData}>
        Refresh
      </button>
    </div>
  )),
}));

// Mock the MerchantContext
jest.mock('@FTUX/context/MerchantContext', () => ({
  useMerchantContext: jest.fn().mockImplementation(() => ({
    onboardingData: {
      merchantOnboardingData: {
        websiteVerificationUpdateStatus: {
          verificationStatus: {},
        },
      },
    },
    refetchAllData: jest.fn().mockReturnValue(Promise.resolve()),
    initiateTwoFaAuth: jest.fn().mockReturnValue(Promise.resolve(true)),
    isRefetchingAllData: false,
  })),
}));

// Mock the mapToWebsiteUpdateData utility function
jest.mock('@FTUX/utils/homepage', () => ({
  mapToWebsiteUpdateData: jest.fn().mockReturnValue(undefined),
}));

describe('AddWebsiteNudge Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('PlatformType enum has correct values', () => {
    expect(PlatformType.WEBSITE).toBe('website');
    expect(PlatformType.APP).toBe('app');
  });

  test('renders with correct title', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    expect(screen.getByText('Setup payment gateway')).toBeInTheDocument();
  });

  test('renders the website verification nudge', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    // Check if product tag is rendered - using within to scope to the first product
    const websiteProduct = screen.getByTestId('product-0');
    expect(within(websiteProduct).getByText('Verification required')).toBeInTheDocument();

    // Check if product title is rendered
    expect(screen.getByText('Accept payments on Website')).toBeInTheDocument();

    // Check if product description is rendered
    expect(
      screen.getByText(
        'Integrating Razorpay Payments is simple and fast. All you need is a live website to get started.',
      ),
    ).toBeInTheDocument();

    // Check if link text is rendered
    expect(within(websiteProduct).getByText('Verify Now')).toBeInTheDocument();
  });

  test('renders only website verification option', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    // Check if website product is rendered
    const websiteProduct = screen.getByTestId('product-0');
    expect(websiteProduct).toHaveTextContent('Verification required');
    expect(websiteProduct).toHaveTextContent('Accept payments on Website');
    expect(websiteProduct).toHaveTextContent(
      'Integrating Razorpay Payments is simple and fast. All you need is a live website to get started.',
    );
    expect(websiteProduct).toHaveTextContent('Verify Now');

    // Ensure only one product is rendered (no app verification option)
    expect(screen.queryByTestId('product-1')).not.toBeInTheDocument();
  });

  test('opens WebsiteV2Modal when Website verification is clicked', async () => {
    const { useMerchantContext } = require('@FTUX/context/MerchantContext');
    const initiateTwoFaAuthMock = jest.fn().mockResolvedValue(true);
    useMerchantContext.mockImplementation(() => ({
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: {},
          },
        },
      },
      refetchAllData: jest.fn().mockReturnValue(Promise.resolve()),
      initiateTwoFaAuth: initiateTwoFaAuthMock,
    }));

    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal for website
    const verifyWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(verifyWebsiteButton);

    // Check if 2FA was initiated
    expect(initiateTwoFaAuthMock).toHaveBeenCalledTimes(1);

    // Check if modal is rendered
    await waitFor(() => {
      expect(screen.getByTestId('website-v2-modal')).toBeInTheDocument();
    });
  });

  test('does not open WebsiteV2Modal when 2FA fails', async () => {
    const { useMerchantContext } = require('@FTUX/context/MerchantContext');
    const initiateTwoFaAuthMock = jest.fn().mockResolvedValue(false);
    useMerchantContext.mockImplementation(() => ({
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: {},
          },
        },
      },
      refetchAllData: jest.fn().mockReturnValue(Promise.resolve()),
      initiateTwoFaAuth: initiateTwoFaAuthMock,
    }));

    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal
    const verifyWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(verifyWebsiteButton);

    // Check if 2FA was initiated
    expect(initiateTwoFaAuthMock).toHaveBeenCalledTimes(1);

    // Modal should not be rendered
    await waitFor(() => {
      expect(screen.queryByTestId('website-v2-modal')).not.toBeInTheDocument();
    });
  });

  test('closes WebsiteV2Modal when onDismiss is called', async () => {
    const { useMerchantContext } = require('@FTUX/context/MerchantContext');
    const initiateTwoFaAuthMock = jest.fn().mockResolvedValue(true);
    useMerchantContext.mockImplementation(() => ({
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: {},
          },
        },
      },
      refetchAllData: jest.fn().mockReturnValue(Promise.resolve()),
      initiateTwoFaAuth: initiateTwoFaAuthMock,
      isRefetchingAllData: false,
    }));

    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal
    const verifyWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(verifyWebsiteButton);

    // Verify modal is open
    await waitFor(() => {
      expect(screen.getByTestId('website-v2-modal')).toBeInTheDocument();
    });

    // Close the modal
    const dismissButton = screen.getByTestId('dismiss-button');
    fireEvent.click(dismissButton);

    // Verify modal is closed
    expect(screen.queryByTestId('website-v2-modal')).not.toBeInTheDocument();
  });

  test('calls refetchAllData when refreshWebsiteData is triggered', async () => {
    const { useMerchantContext } = require('@FTUX/context/MerchantContext');
    const refetchAllDataMock = jest.fn().mockResolvedValue({});
    useMerchantContext.mockImplementation(() => ({
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: {},
          },
        },
      },
      refetchAllData: refetchAllDataMock,
      initiateTwoFaAuth: jest.fn().mockResolvedValue(true),
    }));

    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal
    const verifyWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(verifyWebsiteButton);

    // Verify modal is open
    await waitFor(() => {
      expect(screen.getByTestId('website-v2-modal')).toBeInTheDocument();
    });

    // Trigger refresh
    const refreshButton = screen.getByTestId('refresh-button');
    fireEvent.click(refreshButton);

    // Verify refetchAllData was called
    expect(refetchAllDataMock).toHaveBeenCalledTimes(1);
  });

  test('disables CTA button when loading', async () => {
    const { useMerchantContext } = require('@FTUX/context/MerchantContext');
    useMerchantContext.mockImplementation(() => ({
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: {},
          },
        },
      },
      refetchAllData: jest.fn().mockReturnValue(Promise.resolve()),
      initiateTwoFaAuth: jest.fn().mockImplementation(() => {
        // This creates a delay to test the loading state
        return new Promise((resolve) => setTimeout(() => resolve(true), 100));
      }),
    }));

    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal
    const verifyWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(verifyWebsiteButton);

    // Verify button is disabled during loading
    expect(verifyWebsiteButton).toBeDisabled();
  });

  test('passes correct props to WebsiteV2Modal', async () => {
    const WebsiteV2ModalMock =
      require('@federated/dashboards/payments/components/WebsiteModalsWrapper').default;
    const { useMerchantContext } = require('@FTUX/context/MerchantContext');
    const { mapToWebsiteUpdateData } = require('@FTUX/utils/homepage');

    const mockWebsiteUpdateData = { foo: 'bar' };
    mapToWebsiteUpdateData.mockReturnValue(mockWebsiteUpdateData);

    const refetchAllDataMock = jest.fn();
    useMerchantContext.mockImplementation(() => ({
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: { someData: 'test' },
          },
        },
      },
      refetchAllData: refetchAllDataMock,
      initiateTwoFaAuth: jest.fn().mockResolvedValue(true),
    }));

    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal
    const verifyWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(verifyWebsiteButton);

    // Verify modal is open with correct props
    await waitFor(() => {
      expect(WebsiteV2ModalMock).toHaveBeenCalledWith(
        expect.objectContaining({
          activeStep: WebsiteSubmitModalSteps.ADD_MAIN_PAGE,
          websiteUpdateData: mockWebsiteUpdateData,
          refreshWebsiteData: expect.any(Function),
          onDismiss: expect.any(Function),
        }),
        expect.anything(),
      );
    });
  });
});
