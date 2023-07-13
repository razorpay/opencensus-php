import React from 'react';
import { render, server, waitFor, screen } from 'test-utils';
import { rest } from 'msw';
import OverallCr from 'merchant/views/PaymentMetrics/components/OverallCrGraph';
import moment from 'moment';

const updateServerResponse = () => {
  server.use(
    rest.post('*/live/merchant/analytics', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          data: {
            checkout_overall_cr: {
              result: [
                {
                  timestamp: moment().endOf('hour').unix(),
                  value: 41.328928046989724,
                },
                {
                  timestamp: moment().subtract(2, 'hours').endOf('hour').unix(),
                  value: 65.62106406080348,
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
  test('should render Overall Conversion rate as a with heading', async () => {
    updateServerResponse();
    render(<OverallCr />, {});
    await waitFor(() => {
      expect(screen.queryByText('Overall CR of your Business')).toBeInTheDocument();
    });
  });
  test('should render Overall Conversion rate with dots and canvas graph', async () => {
    updateServerResponse();
    const { container } = render(<OverallCr />, {});
    await waitFor(() => {
      expect(screen.queryByText('Overall CR')).toBeInTheDocument();
      expect(container.getElementsByClassName('chartjs-render-monitor').length).toBe(1);
    });
  });
});
