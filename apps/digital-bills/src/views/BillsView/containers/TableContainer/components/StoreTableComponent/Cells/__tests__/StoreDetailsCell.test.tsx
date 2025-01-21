import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import StoreDetailsCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/StoreDetailsCell';
import { STORE_MOCK } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/__tests__/mocks';

describe('StoreDetailsCell', () => {
  test('should render the StoreDetailsCell component', () => {
    const { getByText, getByRole } = renderWithWrappers(<StoreDetailsCell store={STORE_MOCK} />);
    expect(getByText('1234 - Test Store')).toBeInTheDocument();
    expect(getByText('Store Address')).toBeInTheDocument();
    const brandLogo = getByRole('img');
    expect(brandLogo).toHaveAttribute('src', 'test_brand_logo_url');
    expect(brandLogo).toHaveAttribute('alt', 'Test Store brand logo');
  });
});
