import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import StoreDetailsCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/StoreDetailsCell';
import { STORE_MOCK } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/__tests__/mocks';
import { BRAND_MOCK } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/__tests__/mocks';

describe('StoreDetailsCell', () => {
  test('should render the StoreDetailsCell component', () => {
    const { getByRole, getByText } = renderWithWrappers(
      <StoreDetailsCell store={STORE_MOCK} brand={BRAND_MOCK} />,
    );

    const brandLogo = getByRole('img');
    expect(brandLogo).toBeInTheDocument();
    expect(brandLogo).toHaveAttribute('src', 'test_brand_logo_url');
    expect(brandLogo).toHaveAttribute('alt', 'Test Brand logo');
    expect(getByText('1234 - Test Store')).toBeInTheDocument();
    expect(getByText('Store Address')).toBeInTheDocument();
    expect(getByText('Retail')).toBeInTheDocument();
  });

  test("should render the StoreDetailsCell component for 'E-Commerce'", () => {
    const { getByText } = renderWithWrappers(
      <StoreDetailsCell store={{ ...STORE_MOCK, platform: 'ECOMMERCE' }} brand={BRAND_MOCK} />,
    );

    expect(getByText('E-Commerce')).toBeInTheDocument();
  });
});
