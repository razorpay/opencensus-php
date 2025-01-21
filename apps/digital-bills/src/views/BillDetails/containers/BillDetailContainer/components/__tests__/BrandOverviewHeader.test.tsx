import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import BrandOverviewHeader from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BrandOverviewHeader';

const testData = {
  brandLogo: 'https://assets.billme.co.in/brand/brandlogo-1707219747029-NY%20Cinemas%20Logo.png',
  brandName: 'Starbucks',
  address: 'Test Address',
};

describe('BrandOverviewHeader', () => {
  test('should render the BrandOverviewHeader component', () => {
    const { getByText, getByAltText } = renderWithWrappers(<BrandOverviewHeader {...testData} />);
    const logo = getByAltText(`${testData.brandName} Logo`);
    expect(logo).toHaveAttribute('src', testData.brandLogo);
    expect(getByText(testData.brandName)).toBeInTheDocument();
  });
});
