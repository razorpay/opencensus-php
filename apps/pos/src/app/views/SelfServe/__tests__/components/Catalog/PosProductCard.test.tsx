import React from 'react';

import PosProductCard from 'apps/pos/src/app/views/SelfServe/Catalog/ProductCards/PosProductCard';
import { getMockPropsForPosProductCardComponent } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { render, screen } from 'test-utils';

describe('<PosProductCard/>', () => {
  test('should render PosProductCard with device title', () => {
    const mockProps = getMockPropsForPosProductCardComponent({});
    render(<PosProductCard {...mockProps} />);
    expect(screen.getByText(/Soundbox kit/i)).toBeInTheDocument();
  });
  test('should render title,description,pricing details', () => {
    const mockProps = getMockPropsForPosProductCardComponent({});
    render(<PosProductCard {...mockProps} />);
    expect(screen.getByText(/Soundbox kit/i)).toBeInTheDocument();
    expect(screen.getByText(/Sample description/i)).toBeInTheDocument();
    expect(screen.getByText(/100/i)).toBeInTheDocument();
    expect(screen.getByText(/499/i)).toBeInTheDocument();
    expect(screen.getByText(/\/month/i)).toBeInTheDocument();
  });
  test('should render primary and secondary button', () => {
    const mockProps = getMockPropsForPosProductCardComponent({});
    render(<PosProductCard {...mockProps} />);
    expect(screen.getByRole('button', { name: /add to cart/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /learn more/i })).toBeInTheDocument();
  });
  test('should render offer strip with non-partner text', () => {
    const mockProps = getMockPropsForPosProductCardComponent({});
    render(<PosProductCard {...mockProps} />);
    expect(screen.getByText(/Limited Time Offer till 31st March/i)).toBeInTheDocument();
  });
  test('should render offer strip with partner text', () => {
    const mockProps = getMockPropsForPosProductCardComponent({ isPartnerPricing: true });
    render(<PosProductCard {...mockProps} />);
    expect(screen.getByText(/Partner Exclusive Time Offer till 31st March/i)).toBeInTheDocument();
  });
  test('should not render offer strip when not provided', () => {
    const mockProps = getMockPropsForPosProductCardComponent({ hasOffer: false });
    render(<PosProductCard {...mockProps} />);
    expect(screen.queryByTestId('offer-strip-text')).not.toBeInTheDocument();
  });
  test('should render footer when provided', () => {
    const mockProps = getMockPropsForPosProductCardComponent({
      hasOffer: false,
      footer: {
        title: 'Special offer',
        description: 'Get sticker free',
      },
    });
    render(<PosProductCard {...mockProps} />);
    expect(screen.queryByTestId('pos-catalog-card-footer-title')).toHaveTextContent(
      /Special offer/i,
    );
    expect(screen.queryByTestId('pos-catalog-card-footer-description')).toHaveTextContent(
      /Get sticker free/i,
    );
  });
  test('should not render footer when not provided', () => {
    const mockProps = getMockPropsForPosProductCardComponent({
      hasOffer: false,
    });
    render(<PosProductCard {...mockProps} />);
    expect(screen.queryByTestId('pos-catalog-card-footer-title')).not.toBeInTheDocument();
    expect(screen.queryByTestId('pos-catalog-card-footer-description')).not.toBeInTheDocument();
  });
});
