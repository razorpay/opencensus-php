import React from 'react';
import { render, server, waitFor, screen } from 'test-utils';
import { rest } from 'msw';
import MethodLevelTransactions from 'merchant/views/PaymentMetrics/components/MethodLevelTransactions';
import moment from 'moment';

const updateServerResponse = () => {
  server.use(
    rest.post('*/live/merchant/analytics', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          data: {
            method_level_transactions: {
              result: [
                {
                  timestamp: moment().endOf('hour').unix(),
                  value: 41.328928046989724,
                  last_selected_method: 'upi',
                },
                {
                  timestamp: moment().subtract(2, 'hours').endOf('hour').unix(),
                  value: 65.62106406080348,
                  last_selected_method: 'card',
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

describe('Method Level Transactions Graph', () => {
  test('should render Method Level Transactions rate as a with heading', async () => {
    updateServerResponse();
    render(<MethodLevelTransactions />, {});
    await waitFor(() => {
      expect(screen.queryByText('Method Level Transaction Count')).toBeInTheDocument();
    });
  });
  test('should render Method Level Transactions with dots and canvas graph', async () => {
    updateServerResponse();
    const { container } = render(<MethodLevelTransactions />, {});
    await waitFor(() => {
      expect(screen.queryByText('Method Level Transaction Count')).toBeInTheDocument();
      expect(container.getElementsByClassName('chartjs-render-monitor').length).toBe(1);
    });
  });
});
