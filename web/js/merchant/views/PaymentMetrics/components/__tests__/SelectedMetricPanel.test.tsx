import React from 'react';
import { render, server, waitFor, screen } from 'test-utils';
import { rest } from 'msw';
import SelectedMetricsPanel from 'merchant/views/PaymentMetrics/components/SelectedMetricPanel';
import moment from 'moment';
import { METHOD_LEVEL_CR } from 'merchant/views/PaymentMetrics/constants';

const updateServerResponse = () => {
  server.use(
    rest.post('*/live/merchant/analytics', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          data: {
            checkout_method_level_overall_cr: {
              result: [
                {
                  last_selected_method: 'upi',
                  timestamp: moment().endOf('hour').unix(),
                  value: 98.14814814814815,
                },
                {
                  last_selected_method: 'card',
                  timestamp: moment().subtract(2, 'hours').endOf('hour').unix(),
                  value: 78.18181818181819,
                },
                {
                  last_selected_method: 'cod',
                  timestamp: moment().endOf('hour').unix(),
                  value: 100,
                },
                {
                  last_selected_method: 'wallet',
                  timestamp: moment().subtract(2, 'hours').endOf('hour').unix(),
                  value: 100,
                },
                {
                  last_selected_method: 'null',
                  timestamp: moment().subtract(2, 'hours').endOf('hour').unix(),
                  value: 0,
                },
                {
                  last_selected_method: 'paylater',
                  timestamp: moment().endOf('hour').unix(),
                  value: 100,
                },
                {
                  last_selected_method: 'upi',
                  timestamp: moment().subtract(3, 'hours').endOf('hour').unix(),
                  value: 0,
                },
              ],
            },
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

describe('CR Comparison', () => {
  test('should render Method Level CR as a with heading', async () => {
    updateServerResponse();
    render(<SelectedMetricsPanel handleBack={() => {}} selectedMetric={METHOD_LEVEL_CR} />, {});
    await waitFor(() => {
      expect(screen.queryByText('Method Level CR')).toBeInTheDocument();
    });
  });
  test('should render Method Level rate with dots and canvas graph', async () => {
    updateServerResponse();
    const { container } = render(
      <SelectedMetricsPanel handleBack={() => {}} selectedMetric={METHOD_LEVEL_CR} />,
      {},
    );
    await waitFor(() => {
      expect(container.getElementsByClassName('chartjs-render-monitor').length).toBe(1);
    });
  });
});
