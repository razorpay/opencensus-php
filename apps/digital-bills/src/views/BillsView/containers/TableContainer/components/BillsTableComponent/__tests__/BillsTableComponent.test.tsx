import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { BILLS_TABLE_MOCK_PROPS } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/__tests__/mocks';
import BillsTableComponent from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/BillsTableComponent';

describe('BillsTableComponent', () => {
  test("should render 'BillsTableComponent' as expected", () => {
    const { getByText, getByRole } = renderWithWrappers(
      <BillsTableComponent {...BILLS_TABLE_MOCK_PROPS} />,
    );
    expect(getByText('Bill ID')).toBeInTheDocument();
    expect(getByText('Contact')).toBeInTheDocument();

    expect(getByRole('button', { name: 'bill_1' })).toBeInTheDocument();
    expect(getByText('+91 1234567890')).toBeInTheDocument();
  });
});
