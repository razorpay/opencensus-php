import React from 'react';
import { render, screen } from '@testing-library/react';
import Home from '../index';
import useHomepageState from '@FTUX/hooks/useHomepageState';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';

// Mock the hook
jest.mock('@FTUX/hooks/useHomepageState');

// Mock all child components
jest.mock('../WelcomeHeader', () => () => <div data-testid="welcome-header">Welcome Header</div>);
jest.mock('../AccordionSection', () => () => (
  <div data-testid="accordion-section">Accordion Section</div>
));
jest.mock('../NoCodeSection', () => () => <div data-testid="nocode-section">No Code Section</div>);
jest.mock('../BrowseAllProducts', () => () => (
  <div data-testid="browse-all-products">Browse All Products</div>
));
jest.mock('../PaymentHandle', () => () => <div data-testid="payment-handle">Payment Handle</div>);
jest.mock('../TransactionBanner', () => () => (
  <div data-testid="transaction-banner">Transaction Banner</div>
));
jest.mock('../AddWebsiteNudge', () => () => (
  <div data-testid="add-website-nudge">Add Website Nudge</div>
));
jest.mock('../WaysToCollectPayment', () => () => (
  <div data-testid="ways-to-collect-payment">Ways To Collect Payment</div>
));

describe('Tests for Home Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders PG elements correctly', () => {
    // Mock the hook to return PG elements
    (useHomepageState as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.ACCORDION,
      HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
      HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
      HOMEPAGE_ELEMENTS.BROWSE_ALL,
    ]);

    render(<Home />);

    // Welcome header should always be present
    expect(screen.getByTestId('welcome-header')).toBeInTheDocument();

    // PG elements should be rendered
    expect(screen.getByTestId('accordion-section')).toBeInTheDocument();
    expect(screen.getByTestId('ways-to-collect-payment')).toBeInTheDocument();
    expect(screen.getByTestId('payment-handle')).toBeInTheDocument();
    expect(screen.getByTestId('browse-all-products')).toBeInTheDocument();

    // Other elements should not be rendered
    expect(screen.queryByTestId('nocode-section')).not.toBeInTheDocument();
    expect(screen.queryByTestId('transaction-banner')).not.toBeInTheDocument();
    expect(screen.queryByTestId('add-website-nudge')).not.toBeInTheDocument();
  });

  test('renders No Code elements correctly', () => {
    // Mock the hook to return No Code elements
    (useHomepageState as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
      HOMEPAGE_ELEMENTS.BROWSE_ALL,
      HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
      HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
      HOMEPAGE_ELEMENTS.WEBSITE_NUDGE,
    ]);

    render(<Home />);

    // Welcome header should always be present
    expect(screen.getByTestId('welcome-header')).toBeInTheDocument();

    // No Code elements should be rendered
    expect(screen.getByTestId('nocode-section')).toBeInTheDocument();
    expect(screen.getByTestId('browse-all-products')).toBeInTheDocument();
    expect(screen.getByTestId('payment-handle')).toBeInTheDocument();
    expect(screen.getByTestId('ways-to-collect-payment')).toBeInTheDocument();
    expect(screen.getByTestId('add-website-nudge')).toBeInTheDocument();

    // Other elements should not be rendered
    expect(screen.queryByTestId('accordion-section')).not.toBeInTheDocument();
    expect(screen.queryByTestId('transaction-banner')).not.toBeInTheDocument();
  });

  test('renders Transaction Banner when included in the state', () => {
    // Mock the hook to return elements including transaction banner
    (useHomepageState as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION,
      HOMEPAGE_ELEMENTS.ACCORDION,
      HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
    ]);

    render(<Home />);

    // Welcome header should always be present
    expect(screen.getByTestId('welcome-header')).toBeInTheDocument();

    // Transaction banner should be rendered first
    expect(screen.getByTestId('transaction-banner')).toBeInTheDocument();
    expect(screen.getByTestId('accordion-section')).toBeInTheDocument();
    expect(screen.getByTestId('ways-to-collect-payment')).toBeInTheDocument();
  });
});
