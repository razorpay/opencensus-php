import React from 'react';
import * as graphqlUtils from '@dashboard/shared-utils/graphql/graphql';
import SalesDashboard from '../SalesDashboard';
import { getSalesMappedMerchantsHandler } from './mocks/handlers';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForElementToBeRemoved,
  within,
} from 'apps/pos/src/services/test/test-utils';

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

describe('<SalesDashboard/>', () => {
  test('should render sales dashboard on screen', async () => {
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));
    render(<SalesDashboard />);
    await waitForElementToBeRemoved(screen.getByLabelText('Loading...'));
    expect(screen.getByText('Merchant Details')).toBeInTheDocument();
    const salesTable = screen.getByTestId('sales-table');
    expect(within(salesTable).getByText('OLvMDMRFFdl9TU')).toBeInTheDocument();
    expect(within(salesTable).getByText('+916817163743')).toBeInTheDocument();
    expect(within(salesTable).getByText('Under Review')).toBeInTheDocument();
    expect(within(salesTable).getByText('Raju Body Building')).toBeInTheDocument();
  });

  test('Should trigger gql api with correct payload on filter change', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));
    render(<SalesDashboard />);
    await waitForElementToBeRemoved(screen.getByLabelText('Loading...'));
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
          },
        }),
      );
    });
  });

  test('should render status counts on screen', async () => {
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));
    render(<SalesDashboard />);
    await waitForElementToBeRemoved(screen.getByLabelText('Loading...'));
    const statusCountsContainer = screen.getByTestId('sales-dashboard-status-counts');
    expect(within(statusCountsContainer).getByText('20')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('10')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('1')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('21')).toBeInTheDocument();
    expect(within(statusCountsContainer).getByText('35')).toBeInTheDocument();
  });

  test('should not show status counts if filter on status is added', async () => {
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));
    render(<SalesDashboard />);
    await waitForElementToBeRemoved(screen.getByLabelText('Loading...'));
    const statusFilterContainer = screen.getByTestId('sales-dashboard-filters');
    await userEvent.click(statusFilterContainer);
    await userEvent.click(within(statusFilterContainer).getByText('Activated'));
    expect(screen.queryByTestId('sales-dashboard-status-counts')).toBeNull();
  });

  test('should show empty screen if no merchants are available', async () => {
    server.use(getSalesMappedMerchantsHandler({ type: 'empty' }));
    render(<SalesDashboard />);
    await waitForElementToBeRemoved(screen.getByLabelText('Loading...'));
    expect(
      screen.getByText(`We couldn't find any merchant details associated with your requests`),
    ).toBeInTheDocument();
  });
});
