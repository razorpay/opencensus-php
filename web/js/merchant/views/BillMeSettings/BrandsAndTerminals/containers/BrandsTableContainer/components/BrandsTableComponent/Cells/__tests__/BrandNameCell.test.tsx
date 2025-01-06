import React from 'react';

import { screen, render, userEvent } from 'test-utils';

import BrandNameCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandsTableComponent/Cells/BrandNameCell';
import { BRAND_INFO } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandsTableComponent/Cells/__tests__/mocks';

const App = ({ props }) => <BrandNameCell {...props} />;

describe('BrandNameCell', () => {
  test("should render 'BrandNameCell' component as expected, with the passed in prop value", async () => {
    const handleBrandNameClick = jest.fn();
    const props = {
      tableItem: BRAND_INFO,
      onBrandNameClick: handleBrandNameClick,
    };

    render(<App props={props} />);

    // Logo
    const brandLogo = screen.getByRole('img');
    expect(brandLogo).toHaveAttribute('src', 'test_brand_logo_url');
    expect(brandLogo).toHaveAttribute('alt', 'Test Brand Name logo');

    // Name
    const brandName = screen.getByRole('button');
    expect(brandName).toBeInTheDocument();
    await userEvent.click(brandName);
    expect(handleBrandNameClick).toHaveBeenCalledWith(props.tableItem.id);
  });

  test('should render with the placeholder image for brand logo, when not passed in props', () => {
    const handleBrandNameClick = jest.fn();
    const props = {
      tableItem: { name: 'Test Brand Name' },
      onBrandNameClick: handleBrandNameClick,
    };

    render(<App props={props} />);
    const brandLogo = screen.getByRole('img');
    expect(brandLogo).toHaveAttribute('src', 'brand-logo-placeholder.svg');
  });
});
