import React from 'react';
import { render, server, waitFor, screen } from 'test-utils';
import { rest } from 'msw';
import TotalGmv from 'merchant/views/PaymentMetrics/components/TotalGmv';
import moment from 'moment';

const updateServerResponse = () => {
  server.use(
    rest.post('*/live/merchant/analytics', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          data: {
            checkout_method_level_gmv: {
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

describe('TotalGmv Graph', () => {
  test('should render TotalGmv rate as a with heading', async () => {
    updateServerResponse();
    render(<TotalGmv />, {});
    await waitFor(() => {
      expect(screen.queryByText('Total GMV in Lakhs')).toBeInTheDocument();
    });
  });
  test('should render Overall GMV with dots and canvas graph', async () => {
    updateServerResponse();
    const { container } = render(<TotalGmv />, {});
    await waitFor(() => {
      expect(screen.queryByText('Total GMV in Lakhs')).toBeInTheDocument();
      expect(container.getElementsByClassName('chartjs-render-monitor').length).toBe(1);
    });
  });
});
