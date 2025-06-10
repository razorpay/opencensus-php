import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import NoCodeSection from '../index';

// Mock the PitchProducts component
jest.mock('@FTUX/components/PitchProducts', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ title, subtitle, products }) => (
    <div data-testid="pitch-products">
      <h2 data-testid="pitch-title">{title}</h2>
      <p data-testid="pitch-subtitle">{subtitle}</p>
      <div data-testid="products-list">
        {products.map((product: any, index: number) => (
          <div key={index} data-testid={`product-${index}`} onClick={product.handleClick}>
            <div data-testid={`product-${index}-tag`}>{product.tagText}</div>
            <div data-testid={`product-${index}-title`}>{product.title}</div>
            <div data-testid={`product-${index}-description`}>{product.description}</div>
            <div data-testid={`product-${index}-link`}>{product.linkText}</div>
          </div>
        ))}
      </div>
    </div>
  )),
}));

// Mock the ProductRecommender component
jest.mock('@FTUX/modals/ProductRecommender', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ onDismiss }) => (
    <div data-testid="product-recommender-modal">
      <button data-testid="dismiss-button" onClick={onDismiss}>
        Close
      </button>
    </div>
  )),
}));

describe('NoCodeSection Component', () => {
  test('renders with correct title', () => {
    renderWithWrappers(<NoCodeSection />);

    // Use test IDs to precisely identify the title and subtitle elements
    const titleElement = screen.getByTestId('pitch-title');

    expect(titleElement).toHaveTextContent('Ready-to-use products. No setup needed');
  });

  test('renders all three product options', () => {
    renderWithWrappers(<NoCodeSection />);

    // Check all product tags using test IDs
    expect(screen.getByTestId('product-0-tag')).toHaveTextContent('Personalised to you');
    expect(screen.getByTestId('product-1-tag')).toHaveTextContent('Set up in 2 mins');
    expect(screen.getByTestId('product-2-tag')).toHaveTextContent('Set up in 2 mins');

    // Check all product titles
    expect(screen.getByTestId('product-0-title')).toHaveTextContent(
      'Find the right product for you',
    );
    expect(screen.getByTestId('product-1-title')).toHaveTextContent('Payment Pages');
    expect(screen.getByTestId('product-2-title')).toHaveTextContent('Payment Links');

    // Check all product descriptions
    expect(screen.getByTestId('product-0-description')).toHaveTextContent(
      "Tell us your needs, and we'll recommend the best Razorpay solution for you.",
    );
    expect(screen.getByTestId('product-1-description')).toHaveTextContent(
      'Create a simple checkout page to accept payments online. No website or coding needed.',
    );
    expect(screen.getByTestId('product-2-description')).toHaveTextContent(
      'Generate a link you can share with customers to get paid instantly, without any setup.',
    );

    // Check all link texts
    expect(screen.getByTestId('product-0-link')).toHaveTextContent('Find the right product');
    expect(screen.getByTestId('product-1-link')).toHaveTextContent('Use now');
    expect(screen.getByTestId('product-2-link')).toHaveTextContent('Use now');
  });

  test('passes correct number of products to PitchProducts', () => {
    renderWithWrappers(<NoCodeSection />);

    const products = screen.getAllByTestId(/product-\d+$/);
    expect(products).toHaveLength(3);
  });

  test('correctly structures the products data', () => {
    renderWithWrappers(<NoCodeSection />);

    // Check first product data using test IDs
    expect(screen.getByTestId('product-0-tag')).toHaveTextContent('Personalised to you');
    expect(screen.getByTestId('product-0-title')).toHaveTextContent(
      'Find the right product for you',
    );
    expect(screen.getByTestId('product-0-description')).toHaveTextContent(
      "Tell us your needs, and we'll recommend the best Razorpay solution for you.",
    );
    expect(screen.getByTestId('product-0-link')).toHaveTextContent('Find the right product');

    // Check second product data
    expect(screen.getByTestId('product-1-tag')).toHaveTextContent('Set up in 2 mins');
    expect(screen.getByTestId('product-1-title')).toHaveTextContent('Payment Pages');
    expect(screen.getByTestId('product-1-description')).toHaveTextContent(
      'Create a simple checkout page to accept payments online. No website or coding needed.',
    );
    expect(screen.getByTestId('product-1-link')).toHaveTextContent('Use now');

    // Check third product data
    expect(screen.getByTestId('product-2-tag')).toHaveTextContent('Set up in 2 mins');
    expect(screen.getByTestId('product-2-title')).toHaveTextContent('Payment Links');
    expect(screen.getByTestId('product-2-description')).toHaveTextContent(
      'Generate a link you can share with customers to get paid instantly, without any setup.',
    );
    expect(screen.getByTestId('product-2-link')).toHaveTextContent('Use now');
  });

  test('opens ProductRecommender modal when Find the right product is clicked', () => {
    renderWithWrappers(<NoCodeSection />);

    // Click the first product to trigger the modal
    const firstProductLink = screen.getByTestId('product-0-link');
    firstProductLink.click();

    // Check if the modal is visible
    expect(screen.getByTestId('product-recommender-modal')).toBeInTheDocument();
  });

  test('closes ProductRecommender modal when dismissed', () => {
    renderWithWrappers(<NoCodeSection />);

    // Open the modal first
    const firstProductLink = screen.getByTestId('product-0-link');
    firstProductLink.click();

    // Verify modal is open
    expect(screen.getByTestId('product-recommender-modal')).toBeInTheDocument();

    // Close the modal
    const dismissButton = screen.getByTestId('dismiss-button');
    dismissButton.click();

    // Verify modal is closed
    expect(screen.queryByTestId('product-recommender-modal')).not.toBeInTheDocument();
  });
});
