import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
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
            <div>{product.linkText}</div>
          </div>
        ))}
      </div>
    </div>
  )),
}));

describe('AddWebsiteNudge Component', () => {
  test('renders with correct title and subtitle', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    expect(screen.getByText('Add your website')).toBeInTheDocument();
    expect(screen.getByText('Instant payment collection')).toBeInTheDocument();
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

  test('passes correct number of products to PitchProducts', () => {
    renderWithWrappers(<AddWebsiteNudge />);

    const products = screen.getAllByTestId(/product-\d+/);
    expect(products).toHaveLength(1);
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
});
