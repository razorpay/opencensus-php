import React from 'react';

import { screen, render } from 'test-utils';

import BrandsAndTerminals from 'merchant/views/BillMeSettings/BrandsAndTerminals';

describe('BrandsAndTerminals', () => {
  test("should render 'BrandsAndTerminals' component as expected", () => {
    render(<BrandsAndTerminals />);

    const tabs = screen.getAllByRole('tab');
    expect(tabs[0]).toHaveTextContent('Store Brands');
    expect(tabs[1]).toHaveTextContent('Billing Terminals');
  });
});
