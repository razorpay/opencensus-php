import React from 'react';
import { render, server, screen, waitFor, userEvent, waitForElementToBeRemoved } from 'test-utils';
import SuccessRate from 'merchant/views/Transactions/SuccessRate/containers/SuccessRate';
import {
  errorApiHandler,
  ongoingDowntimesHandler,
  resolvedDowntimesHandler,
  srApiHandler,
} from './mocks/handlers';
import { Provider } from 'react-redux';
import store from 'merchant/store';
import * as services from 'merchant/views/Transactions/SuccessRate/service';

jest.mock('react-chartjs-2', () => {
  const OriginalModule = jest.requireActual('react-chartjs-2');
  return {
    ...OriginalModule,
    Line: ({ data }) => {
      const dataPoints = (data.datasets || []).filter((item) => item.tagName && !item.type);
      return (
        <>
          {dataPoints.map(({ label, data }) => (
            <div data-testid={`${label}-line-chart`} key={label}>
              {(data || []).map((item) => item?.y).join('-')}
            </div>
          ))}
        </>
      );
    },
  };
});

jest.mock('merchant/containers/Home/GroupingDropdown', () => ({
  __esModule: true,
  default: ({ grouping, onGroupChange }) => {
    return (
      <div aria-label="filter-options">
        {grouping.map((item) => (
          <div
            key={item.name}
            onClick={() => onGroupChange({ option: item })}
            aria-label={`${item.value}-filter`}
          >
            {item.name}
          </div>
        ))}
      </div>
    );
  },
}));

jest.mock('common/ui/Forms/SwitchField', () => ({
  __esModule: true,
  default: ({ defaultChecked, onChange }) => (
    <button
      aria-label="failure-reason-toggle"
      onClick={() => onChange(defaultChecked ? 0 : 1, jest.fn())}
      type="button"
    >
      Switch field toggle
    </button>
  ),
}));

jest.mock('merchant/views/Transactions/SuccessRate/helper', () => ({
  ...(jest.requireActual('merchant/views/Transactions/SuccessRate/helper') as typeof Object),
  checkIfFilterValid: () => true,
}));

const App = () => {
  return (
    <Provider store={store}>
      <SuccessRate />
    </Provider>
  );
};

describe('<SuccessRate/>', () => {
  beforeEach(() => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
    );
  });

  test('should display error messages if sr api fails', async () => {
    server.use(srApiHandler({ isSuccess: false }), errorApiHandler({ isSuccess: false }));

    render(<App />);

    await waitFor(() => {
      expect(screen.getByTestId('success-rate-graph-widget')).toHaveTextContent(
        'No payments were made via any payment method in the selected date range',
      );
    });
  });

  test('should render SR dashboard container on screen', async () => {
    server.use(srApiHandler({ isSuccess: true }), errorApiHandler({ isSuccess: true }));

    render(<App />);
    await waitFor(() => {
      expect(screen.getByTestId('success-rate-container')).toBeVisible();
    });
  });

  test('should render loaders for SR metrics on the screen', () => {
    server.use(srApiHandler({ isSuccess: true }), errorApiHandler({ isSuccess: true }));

    render(<App />);

    //Loaders in metrics card
    const metricCardLoaders = screen.getAllByTestId('metrics-card-loader');
    expect(metricCardLoaders[0]).toBeVisible();

    //sr-chart shimmer
    const chartShimmers = screen.getAllByTestId('sr-dashboard-chart-shimmer');
    expect(chartShimmers[0]).toBeVisible();

    //spinner in the success rate chart
    expect(screen.getAllByTestId('spinner')).toHaveLength(2);
  });

  test('should call SR api once only after mount', () => {
    const srFetchAllSpy = jest.spyOn(services, 'getSR');
    server.use(srApiHandler({ isSuccess: true }), errorApiHandler({ isSuccess: true }));

    render(<App />);
    const chartShimmers = screen.getAllByTestId('sr-dashboard-chart-shimmer');
    expect(chartShimmers[0]).toBeVisible();
    expect(srFetchAllSpy).toHaveBeenCalledTimes(1);
  });
  test('should not show method types if not allowed', async () => {
    server.use(srApiHandler({ isSuccess: true }), errorApiHandler({ isSuccess: true }));
    render(<App />);
    const srFetchAllSpy = jest.spyOn(services, 'getSR');

    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card', 'upi', 'netbanking', 'emandate'] },
      }),
    );
    await waitFor(() => {
      expect(screen.getByTestId('Overall-tab')).toHaveClass('active');
    });
    expect(screen.queryByTestId('method-types')).not.toBeInTheDocument();
    await userEvent.click(screen.getByTestId('UPI-tab').firstChild as HTMLElement);
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['upi'] },
      }),
    );
    await waitFor(() => {
      expect(screen.getByTestId('UPI-tab')).toHaveClass('active');
    });
    expect(screen.queryByTestId('method-types')).not.toBeInTheDocument();
    await userEvent.click(screen.getByTestId('Netbanking-tab').firstChild as HTMLElement);
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['netbanking'] },
      }),
    );
    await waitFor(() => {
      expect(screen.getByTestId('Netbanking-tab')).toHaveClass('active');
    });
    expect(screen.queryByTestId('method-types')).not.toBeInTheDocument();
    await userEvent.click(screen.getByTestId('Emandate-tab').firstChild as HTMLElement);
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['emandate'] },
      }),
    );
    await waitFor(() => {
      expect(screen.getByTestId('Emandate-tab')).toHaveClass('active');
    });
    expect(screen.queryByTestId('method-types')).not.toBeInTheDocument();
  });

  test('should show method types upon clicking on card and reflect changes in sr payload', async () => {
    const srFetchAllSpy = jest.spyOn(services, 'getSR');
    server.use(srApiHandler({ isSuccess: true }), errorApiHandler({ isSuccess: true }));
    render(<App />);
    await userEvent.click(screen.getByTestId('Card-tab').firstChild as HTMLElement);
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['credit'] },
      }),
    );
    await waitFor(() => {
      expect(screen.getByLabelText('filter-options')).toBeVisible();
    });
    expect(screen.getByTestId('method-types')).toBeVisible();

    //Debit
    await userEvent.click(screen.getByText('Debit'));
    await waitForElementToBeRemoved(() => screen.getAllByTestId('sr-dashboard-chart-shimmer')[0]);
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['debit'] },
      }),
    );

    //Credit
    await userEvent.click(screen.getByText('Credit'));
    await waitForElementToBeRemoved(() => screen.getAllByTestId('sr-dashboard-chart-shimmer')[0]);
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['credit'] },
      }),
    );

    //Prepaid
    await userEvent.click(screen.getByText('Prepaid'));
    await waitForElementToBeRemoved(() => screen.getAllByTestId('sr-dashboard-chart-shimmer')[0]);
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['prepaid'] },
      }),
    );
  });
  test('should show method types upon clicking on card and reflect changes in error payload', async () => {
    const merchantErrorSpy = jest.spyOn(services, 'getMerchantError');
    server.use(srApiHandler({ isSuccess: true }), errorApiHandler({ isSuccess: true }));
    render(<App />);
    await userEvent.click(screen.getByTestId('Card-tab').firstChild as HTMLElement);
    expect(merchantErrorSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['credit'] },
      }),
    );
    await waitFor(() => {
      expect(screen.getByLabelText('filter-options')).toBeVisible();
    });
    expect(screen.getByTestId('method-types')).toBeVisible();

    //Debit
    await userEvent.click(screen.getByText('Debit'));
    await waitForElementToBeRemoved(() => screen.getAllByTestId('sr-dashboard-chart-shimmer')[0]);
    expect(merchantErrorSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['debit'] },
      }),
    );

    //Credit
    await userEvent.click(screen.getByText('Credit'));
    await waitForElementToBeRemoved(() => screen.getAllByTestId('sr-dashboard-chart-shimmer')[0]);
    expect(merchantErrorSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['credit'] },
      }),
    );

    //Prepaid
    await userEvent.click(screen.getByText('Prepaid'));
    await waitForElementToBeRemoved(() => screen.getAllByTestId('sr-dashboard-chart-shimmer')[0]);
    expect(merchantErrorSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['prepaid'] },
      }),
    );
  });
});

describe('SR Dashboard International', () => {
  const srFetchAllSpy = jest.spyOn(services, 'getSR');

  const selectInternational = async () => {
    await userEvent.click(screen.getByTestId('Card-tab').firstChild as HTMLElement);
    await waitFor(() => {
      expect(screen.getByLabelText('filter-options')).toBeVisible();
    });
    expect(screen.getByLabelText('international-filter')).toBeVisible();
    await userEvent.click(screen.getByLabelText('international-filter'));
  };

  beforeEach(() => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
      srApiHandler({ isSuccess: true }),
      errorApiHandler({ isSuccess: true }),
    );
    render(<App />);
  });

  test('should call fetchSR with correct payload on selecting international', async () => {
    await selectInternational();
    await userEvent.click(screen.getByLabelText('international-filter'));
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        filters: { method: ['card'], type: ['credit'] },
        group_by: { keys: ['international'], limit: 4 },
      }),
    );
  });

  test('should render correct Line chart with pills for international', async () => {
    await selectInternational();
    //check if pills exists
    await waitFor(() => {
      expect(screen.getByTestId('International-chart-tag')).toBeInTheDocument();
    });
    expect(screen.getByTestId('Domestic-chart-tag')).toBeVisible();
    expect(screen.getByTestId('Overall-chart-tag')).toBeVisible();
  });

  test('should render correct Line chart with correct data points for international', async () => {
    await selectInternational();

    await waitFor(() => {
      expect(screen.getByTestId('International-chart-tag')).toBeVisible();
    });
    expect(screen.getByTestId('International-line-chart')).toHaveTextContent(
      '90-91-92-93-94-95-96',
    );
    expect(screen.getByTestId('Domestic-line-chart')).toHaveTextContent('95-96-97-98-99-100');
  });

  test('should render correct Successfull total for domestic and international', async () => {
    await selectInternational();
    await waitFor(() => {
      expect(screen.getByTestId('Overall-info-card-value')).toBeVisible();
    });

    expect(screen.getByTestId('Overall-info-card-value')).toHaveTextContent('17/20 (85%)');
    expect(screen.getByTestId('Domestic-info-card-value')).toHaveTextContent('22/23 (95%)');
    expect(screen.getByTestId('International-info-card-value')).toHaveTextContent('21/23 (91%)');
  });

  test('should call fetchError with correct payload on toggle', async () => {
    const errorFetchSpy = jest.spyOn(services, 'getMerchantError');
    await selectInternational();

    await waitFor(() => {
      expect(screen.getByLabelText('failure-reason-toggle')).toBeVisible();
    });

    userEvent.click(screen.getByLabelText('failure-reason-toggle'));

    await waitFor(() => {
      expect(errorFetchSpy).toHaveBeenLastCalledWith(
        expect.objectContaining({
          filters: { international: ['1'], method: ['card'], type: ['credit'] },
          group_by: { keys: ['international'], limit: 6 },
        }),
      );
    });
  });

  test('should show correct datapoints on toggle and toggle back', async () => {
    await selectInternational();
    // click on a tab
    await userEvent.click(screen.getByLabelText('bank-tab-button'));

    await userEvent.click(screen.getByLabelText('international-filter'));
    await waitFor(() => {
      expect(screen.getByLabelText('failure-reason-toggle')).toBeVisible();
    });
    expect(screen.getByLabelText('bank-tab-button')).toHaveClass('selected');
    expect(screen.getByTestId('failure-reasons-header')).toHaveTextContent(
      'Top payment failure reasons: Banking-related',
    );

    const failureReasonItems = screen.getAllByTestId('failure-reason-item');
    expect(failureReasonItems.length).toBe(2);
  });

  test('should show correct total failure values on toggle and toggle back', async () => {
    await selectInternational();
    // click on a tab
    await userEvent.click(screen.getByLabelText('bank-tab-button'));

    await userEvent.click(screen.getByLabelText('international-filter'));
    await waitFor(() => {
      expect(screen.getByLabelText('failure-reason-toggle')).toBeVisible();
    });

    await userEvent.click(screen.getByLabelText('failure-reason-toggle'));
    await waitFor(() => {
      const content = screen.getAllByTestId('failure-reason-text');
      expect(content[0]).toHaveTextContent('card-credit-international payments failed for credit');
      expect(content[1]).toHaveTextContent(
        'card-credit-international payments not allowed for credit',
      );
    });

    await userEvent.click(screen.getByLabelText('failure-reason-toggle'));
    await waitFor(() => {
      const content = screen.getAllByTestId('failure-reason-text');
      expect(content[0]).toHaveTextContent('card-credit payments failed for credit');
      expect(content[1]).toHaveTextContent('card-credit payments not allowed for credit');
    });
  });
});

describe('SR Admin', () => {
  beforeEach(() => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
      srApiHandler({ isSuccess: true }),
      errorApiHandler({ isSuccess: true }),
    );
    render(<App />);
  });

  test('should not show Admin search if not allowed', () => {
    const srFetchAllSpy = jest.spyOn(services, 'getSR');

    expect(srFetchAllSpy).toHaveBeenCalled();
    expect(() => screen.getByTestId('admin-search')).toThrow();
  });
});
