import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import * as graphqlUtils from '@dashboard/shared-utils/graphql/graphql';
import SalesDashboard from '../SalesDashboard';
import {
  SUCCESS_SALES_MAPPED_MERCHANTS_EMPTY_RESPONSE,
  SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE,
} from './mocks/fixtures';
import { render, screen, userEvent, waitFor, within } from 'apps/pos/src/services/test/test-utils';

jest.setTimeout(30000);

jest.mock('@dashboard/shared-ui/components/Forms/DateRangePickerField', () => {
  return {
    __esModule: true,
    default: ({ onDatesChange }) => (
      <div>
        DateRangePickerField{' '}
        <button
          onClick={() => onDatesChange({ from: 1718755200, to: 1718928000 })}
          data-testid="date-range-test-btn"
        >
          Trigger Date Range Filter Change
        </button>
      </div>
    ),
  };
});

const queryClient = new QueryClient();

const renderApp = () => {
  render(
    <QueryClientProvider client={queryClient}>
      <SalesDashboard />
    </QueryClientProvider>,
  );
};

describe('<SalesDashboard/>', () => {
  beforeEach(() => {
    queryClient.clear();
  });

  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  test('should render sales dashboard on screen', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    graphqlRequestSpy.mockReturnValue(Promise.resolve(SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE));
    renderApp();
    const salesTable = screen.getByTestId('sales-table');
    await waitFor(() => {
      expect(within(salesTable).getByText('OLvMDMRFFdl9TU')).toBeInTheDocument();
    });
  });

  test('Should trigger gql api with correct payload on filter change', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    renderApp();
    expect(screen.getByText('Merchant Details')).toBeInTheDocument();
    await userEvent.click(screen.getByTestId('date-range-test-btn'));
    await userEvent.click(screen.getByText('Apply'));
    await waitFor(() => {
      expect(graphqlRequestSpy).toHaveBeenLastCalledWith(
        expect.objectContaining({
          variables: {
            endDate: 1719014399,
            limit: 10,
            offset: 0,
            startDate: 1718755200,
            status: 'all',
            signupCampaign: 'ASSISTED_ONBOARDING',
          },
        }),
      );
    });
  });

  test('should render status counts on screen', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    graphqlRequestSpy.mockReturnValue(Promise.resolve(SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE));
    renderApp();
    const statusCountsContainer = screen.getByTestId('sales-dashboard-status-counts');
    const salesTable = screen.getByTestId('sales-table');
    await waitFor(() => {
      expect(within(salesTable).getByText('OLvMDMRFFdl9TU')).toBeInTheDocument();
    });
    expect(within(statusCountsContainer).getByText('20')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('10')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('1')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('21')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('35')).toBeInTheDocument();
  });

  test('should not show status counts if filter on status is added', async () => {
    renderApp();
    const statusFilterContainer = screen.getByTestId('sales-dashboard-filters');
    await userEvent.click(statusFilterContainer);
    await screen.getByPlaceholderText('Select Option').click();
    await userEvent.click(screen.getByRole('option', { name: 'Activated' }));
    expect(screen.queryByTestId('sales-dashboard-status-counts')).toBeNull();
  });

  test('should show empty screen if no merchants are available', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    graphqlRequestSpy.mockReturnValue(
      Promise.resolve(SUCCESS_SALES_MAPPED_MERCHANTS_EMPTY_RESPONSE),
    );
    renderApp();
    await waitFor(() => {
      expect(
        screen.getByText(`We couldn't find any merchant details associated with your requests`),
      ).toBeInTheDocument();
    });
  });
});
