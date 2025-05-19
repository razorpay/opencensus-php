import React from 'react';
import { render, screen } from 'test-utils';
import CustomerLoginTable from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerLoginsTable';
import type { SSOLoginDetails } from 'merchant/views/MagicCheckout/SSODashboard/types';

describe('CustomerLoginTable', () => {
  const mockData: SSOLoginDetails[] = [
    {
      login_time: '2024-03-15T10:00:00Z',
      utm_source: 'google',
    },
    {
      login_time: '2024-03-15T11:00:00Z',
      utm_source: 'direct',
    },
  ];

  it('renders table with correct headers', () => {
    render(<CustomerLoginTable data={mockData} isFetching={false} />);

    expect(screen.getByText('Date & Time')).toBeInTheDocument();
    expect(screen.getByText('UTM Source')).toBeInTheDocument();
  });

  it('renders table with data correctly', () => {
    render(<CustomerLoginTable data={mockData} isFetching={false} />);

    expect(screen.getByText('google')).toBeInTheDocument();
    expect(screen.getByText('direct')).toBeInTheDocument();
  });

  it('renders empty state when no data is provided', () => {
    render(<CustomerLoginTable data={[]} isFetching={false} />);

    // The table should still render but with no rows
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.queryByRole('row')).not.toBeInTheDocument();
  });

  it('formats data correctly with unique rows', () => {
    render(<CustomerLoginTable data={mockData} isFetching={false} />);

    const rows = screen.getAllByRole('row');
    expect(rows.length).toBe(mockData.length);
  });
});
