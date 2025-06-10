import React from 'react';
import {
  screen,
  fireEvent,
  waitFor,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import AddWebsiteNudge from '../index';

// Mock the PitchProducts component
jest.mock('@FTUX/components/PitchProducts', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ title, subtitle, products }) => (
    <div data-testid="pitch-products">
      <h2>{title}</h2>
      <p>{subtitle}</p>
      <div data-testid="products-list">
        {products.map((product: any, index: number) => (
          <div key={index} data-testid={`product-${index}`}>
            <div>{product.tagText}</div>
            <div>{product.title}</div>
            <div>{product.description}</div>
            <button data-testid={`product-${index}-link`} onClick={product.handleClick}>
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
  default: jest.fn().mockImplementation(({ onDismiss }) => (
    <div data-testid="website-v2-modal">
      <button data-testid="dismiss-button" onClick={onDismiss}>
        Close
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

  test('renders with correct title', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    expect(screen.getByText('Add your website')).toBeInTheDocument();
  });

  test('renders the website verification nudge', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    // Check if product tag is rendered
    expect(screen.getByText('Website verification is required')).toBeInTheDocument();

    // Check if product title is rendered
    expect(screen.getByText('Payment Gateway on Website/App')).toBeInTheDocument();

    // Check if product description is rendered
    expect(
      screen.getByText('Accept payments on your website or app with a single integration'),
    ).toBeInTheDocument();

    // Check if link text is rendered
    expect(screen.getByText('Add website/app')).toBeInTheDocument();
  });

  test('correctly structures the product data', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    const product = screen.getByTestId('product-0');
    expect(product).toHaveTextContent('Website verification is required');
    expect(product).toHaveTextContent('Payment Gateway on Website/App');
    expect(product).toHaveTextContent(
      'Accept payments on your website or app with a single integration',
    );
    expect(product).toHaveTextContent('Add website/app');
  });

  test('opens WebsiteV2Modal when Add website/app is clicked', async () => {
    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal
    const addWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(addWebsiteButton);

    // Check if modal is rendered
    await waitFor(() => {
      expect(screen.getByTestId('website-v2-modal')).toBeInTheDocument();
    });
  });

  test('closes WebsiteV2Modal when onDismiss is called', async () => {
    renderWithWrappers(<AddWebsiteNudge />);

    // Click the link to open modal
    const addWebsiteButton = screen.getByTestId('product-0-link');
    fireEvent.click(addWebsiteButton);

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
});
