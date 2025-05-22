import React from 'react';
import { screen, waitFor } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import Home from '../App';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';

// Mock the useHomepageState hook
jest.mock('@FTUX/hooks/useHomepageState', () => ({
  __esModule: true,
  default: jest.fn(),
}));

// Mock the lazy-loaded components
jest.mock('../WelcomeHeader', () => ({
  __esModule: true,
  default: () => <div data-testid="welcome-header">Welcome Header</div>,
}));

jest.mock('../AccordionSection', () => ({
  __esModule: true,
  default: () => <div data-testid="accordion-section">Accordion Section</div>,
}));

jest.mock('../NoCodeSection', () => ({
  __esModule: true,
  default: () => <div data-testid="nocode-section">No Code Section</div>,
}));

jest.mock('../BrowseAllProducts', () => ({
  __esModule: true,
  default: () => <div data-testid="browse-all">Browse All Products</div>,
}));

jest.mock('../PaymentHandle', () => ({
  __esModule: true,
  default: () => <div data-testid="payment-handle">Payment Handle</div>,
}));

jest.mock('../TransactionBanner', () => ({
  __esModule: true,
  default: () => <div data-testid="transaction-banner">Transaction Banner</div>,
}));

jest.mock('../AddWebsiteNudge', () => ({
  __esModule: true,
  default: () => <div data-testid="website-nudge">Add Website Nudge</div>,
}));

jest.mock('../WaysToAcceptPayment', () => ({
  __esModule: true,
  default: () => <div data-testid="ways-for-payment">Ways to Collect Payment</div>,
}));

// Import the mocked hook
import useHomepageState from '@FTUX/hooks/useHomepageState';

// Mock page layout loader
jest.mock('apps/onboarding-experience/src/common/components/PageLayoutLoader', () => ({
  PageLayoutLoader: () => <div data-testid="page-loader">Loading...</div>,
}));

describe('Home Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders loader when homepageState is empty', () => {
    // Mock hook to return empty array
    (useHomepageState as jest.Mock).mockReturnValue([]);

    renderWithWrappers(<Home />);

    // Should display the loader
    expect(screen.getByTestId('page-loader')).toBeInTheDocument();
  });

  test('renders WelcomeHeader and components based on homepageState', async () => {
    // Mock hook to return specific homepage elements
    (useHomepageState as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.ACCORDION,
      HOMEPAGE_ELEMENTS.BROWSE_ALL,
    ]);

    renderWithWrappers(<Home />);

    // Wait for lazy-loaded components to render
    await waitFor(() => {
      expect(screen.getByTestId('welcome-header')).toBeInTheDocument();
      expect(screen.getByTestId('accordion-section')).toBeInTheDocument();
      expect(screen.getByTestId('browse-all')).toBeInTheDocument();
    });
  });

  test('renders the transaction banner when included in homepageState', async () => {
    // Mock hook to return state with transaction banner
    (useHomepageState as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION,
      HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
    ]);

    renderWithWrappers(<Home />);

    // Wait for lazy-loaded components to render
    await waitFor(() => {
      expect(screen.getByTestId('welcome-header')).toBeInTheDocument();
      expect(screen.getByTestId('transaction-banner')).toBeInTheDocument();
      expect(screen.getByTestId('payment-handle')).toBeInTheDocument();
    });
  });

  test('renders NoCode section when included in homepageState', async () => {
    // Mock hook to return state with NoCode section
    (useHomepageState as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
      HOMEPAGE_ELEMENTS.WEBSITE_NUDGE,
    ]);

    renderWithWrappers(<Home />);

    // Wait for lazy-loaded components to render
    await waitFor(() => {
      expect(screen.getByTestId('welcome-header')).toBeInTheDocument();
      expect(screen.getByTestId('nocode-section')).toBeInTheDocument();
      expect(screen.getByTestId('website-nudge')).toBeInTheDocument();
    });
  });

  test('renders all possible components when all elements are included', async () => {
    // Mock hook to return all homepage elements
    (useHomepageState as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION,
      HOMEPAGE_ELEMENTS.ACCORDION,
      HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
      HOMEPAGE_ELEMENTS.BROWSE_ALL,
      HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
      HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
      HOMEPAGE_ELEMENTS.WEBSITE_NUDGE,
    ]);

    renderWithWrappers(<Home />);

    // Wait for all lazy-loaded components to render
    await waitFor(() => {
      expect(screen.getByTestId('welcome-header')).toBeInTheDocument();
      expect(screen.getByTestId('transaction-banner')).toBeInTheDocument();
      expect(screen.getByTestId('accordion-section')).toBeInTheDocument();
      expect(screen.getByTestId('nocode-section')).toBeInTheDocument();
      expect(screen.getByTestId('browse-all')).toBeInTheDocument();
      expect(screen.getByTestId('payment-handle')).toBeInTheDocument();
      expect(screen.getByTestId('ways-for-payment')).toBeInTheDocument();
      expect(screen.getByTestId('website-nudge')).toBeInTheDocument();
    });
  });
});
