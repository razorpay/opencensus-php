import React from 'react';

import TermsAndConditions from 'apps/pos/src/app/views/SelfServe/ProductDescription/TermsAndConditions';
import {
  MOCK_PRODUCT,
  MOCK_PRICING_WITH_PRICES,
  MOCK_PRODUCT_PRICING_RESPONSE,
  MOCK_PRODUCT_OFFER_CONFIG,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductDescriptionWithPricingPlan } from 'apps/pos/src/app/views/SelfServe/helpers';
import { ProductDescription } from 'apps/pos/src/app/views/SelfServe/types';
import { render, screen } from 'test-utils';

describe('<TermsAndConditions/>', () => {
  test('should render Terms and conditions component on screen with heading', () => {
    render(
      <TermsAndConditions
        title="Terms & Conditions"
        product={{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }}
      />,
    );
    expect(screen.getByText('Terms & Conditions')).toBeVisible();
    expect(screen.getByText('Monthly Plan Pricing')).toBeVisible();
    expect(screen.getByText('Lifetime Pricing')).toBeVisible();
    expect(
      screen.getByText(
        'There will be a 1 (one) year manufacturing warranty on POS Devices. The terms of warranty shall be in accordance with OEM’s policy.',
      ),
    ).toBeVisible();
  });

  test('should render Terms and conditions component with offers on screen with heading and content if offer exists', () => {
    const description = getProductDescriptionWithPricingPlan({
      productCode: 'mock-product',
      pricingPlanDict: MOCK_PRODUCT_PRICING_RESPONSE,
      offerConfigForProduct: MOCK_PRODUCT_OFFER_CONFIG['mock-product'],
    });

    render(
      <TermsAndConditions
        title="Offer Terms & Conditions"
        product={description as ProductDescription}
        isShowPricing
      />,
    );
    expect(screen.getByText('Offer Terms & Conditions')).toBeVisible();
    expect(screen.getByText('Monthly Plan Pricing')).toBeVisible();
    expect(screen.getByText('Proceed with monthly pricing')).toBeVisible();
    expect(screen.getByText('Proceed with lifetime pricing')).toBeVisible();
    expect(screen.getAllByTestId('pos-offer-price-cards').length).toBe(2);
  });
});
