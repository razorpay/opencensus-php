import React from 'react';
import { useQuery } from '@tanstack/react-query';

import EntityAnalyticsTable from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityAnalyticsTable';
import { render, screen, waitFor } from 'test-utils';

import { expectedAmountData, mock_data } from './mocks';
import { FRAUD } from '../../constants';
import { EntityAnalyticsTableProps } from '../EntityAnalyticsTable';

const useQueryMock = useQuery as jest.Mock;

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

describe('EntityAnalyticsTable - Risk Visibility', () => {
  let mockProps: EntityAnalyticsTableProps;

  beforeEach(() => {
    mockProps = {
      entity: FRAUD,
      dateRange: {
        startDate: null,
        endDate: null,
        preset: { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
      },
    };
    useQueryMock.mockReturnValue({ isLoading: true, isError: false, data: undefined });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const renderComponent = (props: Partial<EntityAnalyticsTableProps> = {}) => {
    const mergedProps = { ...mockProps, ...props };
    return render(<EntityAnalyticsTable {...mergedProps} />);
  };

  test('renders EntityAnalyticsTable without error', () => {
    renderComponent();
    expect(screen.getByTestId('analytics-table')).toBeVisible();
  });

  test('renders table metric selector', () => {
    renderComponent();
    expect(screen.getByTestId('metric-selector')).toBeVisible();
    expect(screen.getByText('count')).toBeVisible();
    expect(screen.getByText('amount')).toBeVisible();
  });

  test('renders table headers', () => {
    renderComponent();
    // Assuming FRAUD entity is used for this test
    const expectedColumnHeaders = ['Card BIN', 'No. of txns', 'No. of frauds', 'Fraud rate'];

    // Assert that each column header exists within the TableHead component
    expectedColumnHeaders.forEach((header) => {
      const columnHeader = screen.getByText(header, { selector: 'th' }); // Find the column header by its text content within <th> elements
      expect(columnHeader).toBeInTheDocument(); // Assert that the column header is present in the document
    });
  });

  test('renders table skeleton loader', async () => {
    renderComponent();
    await waitFor(() => {
      const skeletonTableRows = screen.getAllByTestId('skeleton-table-row');
      expect(skeletonTableRows).toHaveLength(8);
    });
  });

  test('renders error state', async () => {
    useQueryMock.mockReturnValue({ isLoading: false, isError: true, data: undefined });
    renderComponent();
    await waitFor(() => {
      expect(screen.getByText('Fetching failed! Try later')).toBeInTheDocument();
    });
  });

  test('should render table no content text', () => {
    useQueryMock.mockReturnValue({
      isLoading: false,
      isError: false,
      data: mock_data,
    });

    renderComponent();
    const tableCells = screen.getAllByRole('cell');
    expectedAmountData.forEach((rowData, rowIndex) => {
      const startIndex = rowIndex * 4;
      const rowValues = tableCells
        .slice(startIndex, startIndex + 4)
        .map((cell) => cell.textContent);
      expect(rowValues).toEqual(Object.values(rowData));
    });

    const emptyRow = screen.getByTestId('empty-row');
    expect(emptyRow).toBeInTheDocument();
    const emptyRowStyle = window.getComputedStyle(emptyRow);
    const emptyRowHeight = parseInt(emptyRowStyle.height, 10);
    expect(emptyRowHeight).toEqual(225);
  });
});
