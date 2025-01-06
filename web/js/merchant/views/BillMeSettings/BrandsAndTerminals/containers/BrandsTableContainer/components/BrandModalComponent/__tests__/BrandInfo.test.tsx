import React from 'react';

import BrandInfo from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/BrandInfo';
import { BRAND_BY_ID_RESPONSE } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/__tests__/mocks';
import { screen, render } from 'test-utils';

const App = ({ props }) => <BrandInfo {...props} />;

describe('BrandInfo', () => {
  test("should render 'BrandInfo' component as expected, with the passed in prop value", () => {
    const props = { selectedBrandInfo: BRAND_BY_ID_RESPONSE };

    render(<App props={props} />);
    expect(screen.getByText('Image')).toBeInTheDocument();
    const brandImageElement = screen.getByRole('img');
    expect(brandImageElement).toBeInTheDocument();
    expect(brandImageElement).toHaveAttribute('src', 'test_image_url');
    expect(screen.getByText('Name')).toBeInTheDocument();
    expect(screen.getByText('Test Brand Name')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('Test Brand Description')).toBeInTheDocument();
  });

  test("should render 'BrandInfo' component with the default value for image and description, when not passed in props", () => {
    const props = {
      selectedBrandInfo: {
        name: 'Test Brand',
        description: null,
        logo: null,
      },
    };

    render(<App props={props} />);
    const brandImageElement = screen.getByRole('img');
    expect(brandImageElement).toBeInTheDocument();
    expect(brandImageElement).toHaveAttribute('src', 'brandInfo-image-placeholder.svg');
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('-')).toBeInTheDocument();
  });
});
