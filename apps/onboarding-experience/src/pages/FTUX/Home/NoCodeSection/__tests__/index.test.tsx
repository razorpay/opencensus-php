import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import NoCodeSection from '../index';

// Mock the PitchProducts component
jest.mock('@FTUX/Home/PitchProducts', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ title, subtitle, products }) => (
    <div data-testid="pitch-products">
      <h2 data-testid="pitch-title">{title}</h2>
      <p data-testid="pitch-subtitle">{subtitle}</p>
      <div data-testid="products-list">
        {products.map((product: any, index: number) => (
          <div key={index} data-testid={`product-${index}`}>
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

describe('NoCodeSection Component', () => {
  test('renders with correct title and subtitle', () => {
    renderWithWrappers(<NoCodeSection />);

    // Use test IDs to precisely identify the title and subtitle elements
    const titleElement = screen.getByTestId('pitch-title');
    const subtitleElement = screen.getByTestId('pitch-subtitle');

    expect(titleElement).toHaveTextContent('Ready-to-use products');
    expect(subtitleElement).toHaveTextContent('Instant payment collection');
  });

  test('renders all three product options', () => {
    renderWithWrappers(<NoCodeSection />);

    // Check all product tags using test IDs
    expect(screen.getByTestId('product-0-tag')).toHaveTextContent('Payment');

    // Check for "Set up in 2 mins" in the correct locations
    expect(screen.getByTestId('product-1-tag')).toHaveTextContent('Set up in 2 mins');
    expect(screen.getByTestId('product-2-tag')).toHaveTextContent('Set up in 2 mins');

    // Check all product titles
    expect(screen.getByTestId('product-0-title')).toHaveTextContent('Instant payment collection');
    expect(screen.getByTestId('product-1-title')).toHaveTextContent('Payment Pages');
    expect(screen.getByTestId('product-2-title')).toHaveTextContent('Payment Links');

    // Check all product descriptions
    expect(screen.getByTestId('product-0-description')).toHaveTextContent(
      'Accept payments on your website with a single integration',
    );
    expect(screen.getByTestId('product-1-description')).toHaveTextContent(
      'Get your own checkout page to sell/ accept payment online, even without a website.',
    );
    expect(screen.getByTestId('product-2-description')).toHaveTextContent(
      'Share a link on WhatsApp, SMS, or email and get paid immediately.',
    );

    // Check all link texts
    expect(screen.getByTestId('product-0-link')).toHaveTextContent('Learn more');
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
    expect(screen.getByTestId('product-0-tag')).toHaveTextContent('Payment');
    expect(screen.getByTestId('product-0-title')).toHaveTextContent('Instant payment collection');
    expect(screen.getByTestId('product-0-description')).toHaveTextContent(
      'Accept payments on your website with a single integration',
    );
    expect(screen.getByTestId('product-0-link')).toHaveTextContent('Learn more');

    // Check second product data
    expect(screen.getByTestId('product-1-tag')).toHaveTextContent('Set up in 2 mins');
    expect(screen.getByTestId('product-1-title')).toHaveTextContent('Payment Pages');
    expect(screen.getByTestId('product-1-description')).toHaveTextContent(
      'Get your own checkout page to sell/ accept payment online, even without a website.',
    );
    expect(screen.getByTestId('product-1-link')).toHaveTextContent('Use now');

    // Check third product data
    expect(screen.getByTestId('product-2-tag')).toHaveTextContent('Set up in 2 mins');
    expect(screen.getByTestId('product-2-title')).toHaveTextContent('Payment Links');
    expect(screen.getByTestId('product-2-description')).toHaveTextContent(
      'Share a link on WhatsApp, SMS, or email and get paid immediately.',
    );
    expect(screen.getByTestId('product-2-link')).toHaveTextContent('Use now');
  });
});
