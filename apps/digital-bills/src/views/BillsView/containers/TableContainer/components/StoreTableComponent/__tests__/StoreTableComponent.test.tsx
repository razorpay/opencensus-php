import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { STORE_TABLE_MOCK_PROPS } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/__tests__/mocks';
import StoresTableComponent from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/StoreTableComponent';

describe('StoresTableComponent', () => {
  test("should render 'StoresTableComponent' as expected", () => {
    const { getByText } = renderWithWrappers(
      <StoresTableComponent tableProps={STORE_TABLE_MOCK_PROPS} />,
    );
    expect(getByText('Store Details')).toBeInTheDocument();
    expect(getByText('Status')).toBeInTheDocument();
    expect(getByText('Total Sales')).toBeInTheDocument();
    expect(getByText('Average Billing')).toBeInTheDocument();
    expect(getByText('Total Transactions')).toBeInTheDocument();
    expect(getByText('Digital Bills')).toBeInTheDocument();
    expect(getByText('Digital + Print')).toBeInTheDocument();
    expect(getByText('Print')).toBeInTheDocument();

    expect(getByText('1234 - Test Store')).toBeInTheDocument();
    expect(getByText('Active')).toBeInTheDocument();
    expect(getByText('100')).toBeInTheDocument();
    expect(getByText('200')).toBeInTheDocument();
    expect(getByText('300')).toBeInTheDocument();
    expect(getByText('400')).toBeInTheDocument();
    expect(getByText('500')).toBeInTheDocument();
    expect(getByText('600')).toBeInTheDocument();
  });

  test("should render 'StoresTableComponent' with placeholder when no stores are found", () => {
    const { getByText } = renderWithWrappers(
      <StoresTableComponent tableProps={{ ...STORE_TABLE_MOCK_PROPS, storesData: [] }} />,
    );
    expect(getByText('No data found')).toBeInTheDocument();
  });
});
