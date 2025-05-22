import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';
import { useQuery } from '@tanstack/react-query';
import ResellerTable from '../index';
import { RESELLER_SERVICE } from '../constants';

// Mock dependencies
jest.mock('@tanstack/react-query', () => ({
  ...jest.requireActual('@tanstack/react-query'),
  useQuery: jest.fn(),
}));

const mockResellersData = {
  items: [
    {
      id: 'reseller_1',
      merchant_id: 'merchant_id_1',
      merchant_name: 'merchant_1',
      elligible_programs: '1,2,3,4',
      order_count: 10,
      aggregate_order_value: 1000,
      status: 'active',
    },
    {
      id: 'reseller_2',
      merchant_id: 'merchant_id_2',
      merchant_name: 'merchant_2',
      elligible_programs: '1,2,3,4',
      order_count: 5,
      aggregate_order_value: 50,
      status: 'active',
    },
  ],
  total_count: 2,
};

describe('ResellerTable', () => {
  const defaultProps = {
    mode: 'test',
    merchantId: 'merchant_123',
    showFilters: true,
    service: RESELLER_SERVICE.ALL_RESELLERS,
    renderLoading: <div data-testid="reseller-table-loading">Loading...</div>,
    onSelection: jest.fn(),
    refetchQuery: 0,
    filterOptions: {},
    programId: 'program_id_1',
    pageSize: 10,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useQuery as jest.Mock).mockReturnValue({
      data: mockResellersData,
      isLoading: false,
      error: null,
      isError: false,
    });
  });

  test('renders table with reseller data', () => {
    render(<ResellerTable {...defaultProps} />);

    // Check if table headers are rendered
    expect(screen.getByText('Reseller ID')).toBeInTheDocument();
    expect(screen.getByText('Reseller Name')).toBeInTheDocument();
    expect(screen.getByText('Eligible Programs')).toBeInTheDocument();
    expect(screen.getByText('Order Count')).toBeInTheDocument();
    expect(screen.getByText('Order Value')).toBeInTheDocument();
    expect(screen.getAllByText('Status')[0]).toBeInTheDocument();

    // Check if reseller data is rendered
    expect(screen.getByText('merchant_id_1')).toBeInTheDocument();
    expect(screen.getByText('merchant_1')).toBeInTheDocument();
    expect(screen.getAllByText('10')[0]).toBeInTheDocument();

    expect(screen.getByText('merchant_id_2')).toBeInTheDocument();
    expect(screen.getByText('merchant_2')).toBeInTheDocument();
    expect(screen.getAllByText('5')[0]).toBeInTheDocument();
  });

  test('shows loading state', () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: true,
      data: null,
    });

    render(<ResellerTable {...defaultProps} />);
    expect(screen.getByTestId('reseller-table-loading')).toBeInTheDocument();
  });

  test('handles empty resellers list', () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: { items: [], total_count: 0 },
      isLoading: false,
    });

    render(<ResellerTable {...defaultProps} />);
    expect(screen.getByText('There are no resellers yet!!')).toBeInTheDocument();
  });
});
