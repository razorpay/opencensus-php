import React from 'react';

import DetailedPricing from 'merchant/views/POS/ProductDescription/DetailedPricing';
import {
  MOCK_PRODUCT,
  MOCK_PRICING_WITH_PRICES,
  MOCK_PRODUCT_OFFER_CONFIG,
  MOCK_PRODUCT_PRICING_RESPONSE,
  MOCK_PARTNER_PRODUCT_PRICING_RESPONSE,
  MOCK_DETAILED_PRICING,
} from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductDescriptionWithPricingPlan } from 'merchant/views/POS/helpers';
import { ProductDescription } from 'merchant/views/POS/types';
import { render, screen } from 'test-utils';

describe('<DetailedPricing/>', () => {
  test('should render Detailed pricing component on screen with heading', () => {
    render(
      <DetailedPricing
        pricingDetails={MOCK_DETAILED_PRICING}
        product={{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }}
      />,
    );
    expect(screen.getByText('Detailed Pricing')).toBeVisible();
    expect(screen.getByText('Particulars')).toBeVisible();
    expect(screen.getByText('MDR')).toBeVisible();
  });

  test('should render Detailed pricing component on screen with rows and row name', () => {
    render(
      <DetailedPricing
        pricingDetails={MOCK_DETAILED_PRICING}
        product={{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }}
      />,
    );
    expect(screen.getByText('Credit Card (Visa/Master/Rupay)')).toBeVisible();
    expect(screen.getByText('International Card/Corp cards/Amex/Diners')).toBeVisible();
    expect(screen.getByText('2.75%')).toBeVisible();
  });

  test('should render Detailed pricing footer text on screen', () => {
    render(
      <DetailedPricing
        pricingDetails={MOCK_DETAILED_PRICING}
        product={{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }}
      />,
    );
    expect(screen.getByTestId('detailed-pricing-footer-text')).toBeVisible();
  });

  test('should render Detailed pricing with offer content if offer exists on screen', () => {
    const description = getProductDescriptionWithPricingPlan({
      productCode: 'mock-product',
      pricingPlanDict: MOCK_PRODUCT_PRICING_RESPONSE,
      offerConfigForProduct: MOCK_PRODUCT_OFFER_CONFIG['mock-product'],
    });

    render(
      <DetailedPricing
        pricingDetails={MOCK_DETAILED_PRICING}
        product={description as ProductDescription}
      />,
    );
    expect(screen.getByText('Mock offer text')).toBeVisible();
    expect(screen.getByText('After 1L GMV, Below Rates to Apply')).toBeVisible();
    expect(screen.getByText(/charges upto ₹1L transactions/)).toBeVisible();
    expect(screen.getByText('1.75%')).toBeVisible();
    expect(screen.getByText('1.85%')).toBeVisible();
  });

  test('should render Detailed pricing with partner offer content if offer exists on screen', () => {
    const description = getProductDescriptionWithPricingPlan({
      productCode: 'mock-product',
      pricingPlanDict: MOCK_PARTNER_PRODUCT_PRICING_RESPONSE,
      offerConfigForProduct: MOCK_PRODUCT_OFFER_CONFIG['mock-product'],
    });

    render(
      <DetailedPricing
        pricingDetails={MOCK_DETAILED_PRICING}
        product={description as ProductDescription}
      />,
    );
    expect(screen.getByText('Mock partner offer text')).toBeVisible();
    expect(screen.getByText('Offer ends after 3 months')).toBeVisible();
    expect(screen.getByText(/charges upto ₹1L transactions/)).toBeVisible();
  });
});
