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
    await waitForElementToBeRemoved(screen.getByText('Loading...'));
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
    await waitForElementToBeRemoved(screen.getByText('Loading...'));
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
});
