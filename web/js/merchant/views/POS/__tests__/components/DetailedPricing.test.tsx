import React from 'react';
import { render, screen } from 'test-utils';

import DetailedPricing from 'merchant/views/POS/ProductDescription/DetailedPricing';

describe('<DetailedPricing/>', () => {
  test('should render Detailed pricing component on screen with heading', () => {
    render(<DetailedPricing />);
    expect(screen.getByText('Detailed Pricing')).toBeVisible();
    expect(screen.getByText('Particulars')).toBeVisible();
    expect(screen.getByText('MDR')).toBeVisible();
  });

  test('should render Detailed pricing component on screen with rows and row name', () => {
    render(<DetailedPricing />);
    expect(screen.getByText('Credit Card (Visa/Master/Rupay)')).toBeVisible();
    expect(screen.getByText('International Card/Corp cards/Amex/Diners')).toBeVisible();
    expect(screen.getByText('3.00%')).toBeVisible();
  });

  test('should render Detailed pricing footer text on screen', () => {
    render(<DetailedPricing />);
    expect(screen.getByTestId('detailed-pricing-footer-text')).toBeVisible();
  });
});
