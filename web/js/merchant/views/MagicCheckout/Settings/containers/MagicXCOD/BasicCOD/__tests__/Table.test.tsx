import React from 'react';
import { render, screen, within } from '@testing-library/react';

import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { CODTable } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/components/Table';

import {
  action,
  COLUMNS,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/constants';

import { mockShippingMethods } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/__tests__/mocks';

describe('CODTable Component', () => {
  const columns = [...Object.keys(COLUMNS).map((COLUMN) => COLUMNS[COLUMN]), action(() => '')];

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <CODTable {...props} />
      </BladeProvider>
    );
  };
  test('Should render table with correct column headers and rows', () => {
    render(<App shippingMethods={mockShippingMethods} isLoading={false} columns={columns} />);

    const rows = screen.getAllByRole('row');
    const rowHeader = screen.getByRole('rowheader');

    //Column Headers
    expect(within(rowHeader).getByText('Shipping Method')).toBeInTheDocument();
    expect(within(rowHeader).getByText('COD')).toBeInTheDocument();
    expect(within(rowHeader).getByText('Prepaid')).toBeInTheDocument();
    expect(within(rowHeader).getByText('Shipping Rate')).toBeInTheDocument();
    expect(within(rowHeader).getByText('COD Order Range')).toBeInTheDocument();
    expect(within(rowHeader).getByText('Action')).toBeInTheDocument();

    // First Row
    expect(within(rows[0]).getByText(/profile1/i)).toBeInTheDocument();
    expect(within(rows[0]).getByText('Enabled')).toBeInTheDocument();
    expect(within(rows[0]).getByText('Disabled')).toBeInTheDocument();
    expect(within(rows[0]).getByText('₹1.00')).toBeInTheDocument();
    expect(within(rows[0]).getByText('₹0.00 - ₹1,000.00')).toBeInTheDocument();
    expect(within(rows[0]).getByText('Configure COD')).toBeInTheDocument();
  });

  test('Should not renders rows when isLoading is true', () => {
    render(<App shippingMethods={[]} isLoading={true} columns={columns} />);

    const rows = screen.queryAllByRole('row');
    expect(rows).toHaveLength(0);
  });

  test('Should display correct number of rows in the table once data is available', () => {
    render(<App shippingMethods={mockShippingMethods} isLoading={false} columns={columns} />);

    // Check the number of rendered rows
    const rows = screen.getAllByRole('row');
    expect(rows).toHaveLength(mockShippingMethods.length);
  });

  test('Should render pagination controls', () => {
    render(<App shippingMethods={mockShippingMethods} isLoading={false} columns={columns} />);

    const paginationDropdown = screen.getByRole('combobox');
    // Default Page Size 10 rows;
    expect(within(paginationDropdown).getByText('10')).toBeInTheDocument();
    expect(screen.getByText('rows / page')).toBeInTheDocument();
  });
});
