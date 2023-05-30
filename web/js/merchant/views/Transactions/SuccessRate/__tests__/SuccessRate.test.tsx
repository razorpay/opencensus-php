import React from 'react';
import { render, server, screen, waitFor } from 'test-utils';
import SuccessRate from 'merchant/views/Transactions/SuccessRate/containers/SuccessRate';
import {
  errorApiHandler,
  ongoingDowntimesHandler,
  resolvedDowntimesHandler,
  srApiHandler,
} from './mocks/handlers';
import { Provider } from 'react-redux';
import store from 'merchant/store';

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
});
