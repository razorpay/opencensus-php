import React from 'react';
import { render, screen } from 'test-utils';

import TermsAndConditions from 'merchant/views/POS/ProductDescription/TermsAndConditions';

describe('<TermsAndConditions/>', () => {
  test('should render Terms and conditions component on screen with heading', () => {
    render(<TermsAndConditions />);
    expect(screen.getByText('Terms & Conditions')).toBeVisible();
  });

  test('should render Terms and conditions criterias on screen', () => {
    render(<TermsAndConditions />);
    expect(screen.getByText('Monthly Plan Pricing')).toBeVisible();
    expect(screen.getByText('Lifetime Pricing')).toBeVisible();
  });

  test('should render Terms and conditions row on screen', () => {
    render(<TermsAndConditions />);
    expect(
      screen.getByText('The above pricing is inclusive of sim card and paper roll cost.'),
    ).toBeVisible();
  });
});
