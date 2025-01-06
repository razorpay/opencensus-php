import React from 'react';

import { screen, render, fireEvent } from 'test-utils';

import BrandsAndTerminals from 'merchant/views/BillMeSettings/BrandsAndTerminals';

describe('BrandsAndTerminals', () => {
  test("should render 'BrandsAndTerminals' component as expected", () => {
    render(<BrandsAndTerminals />);

    const tabs = screen.getAllByRole('tab');
    expect(tabs[0]).toHaveTextContent('Store Brands');
    expect(tabs[1]).toHaveTextContent('Billing Terminals');

    // Active tab (Store Brands)
    expect(screen.getByRole('button', { name: /New Brand/ })).toBeInTheDocument();

    // Switch active tab
    fireEvent.click(tabs[1]);
    expect(screen.getByText('Terminal Key')).toBeInTheDocument();
  });
});
