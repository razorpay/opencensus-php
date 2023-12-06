import React from 'react';
import { render, server, waitFor, screen } from 'test-utils';
import { rest } from 'msw';
import MethodLevelGmv from 'merchant/views/PaymentMetrics/components/MethodLevelGmv';
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
                  value: 420000,
                  last_selected_method: 'upi',
                },
                {
                  timestamp: moment().subtract(2, 'hours').endOf('hour').unix(),
                  value: 340987100,
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

describe('Method Level Gmv Graph', () => {
  test('should render Method Level Gmv as a with heading', async () => {
    updateServerResponse();
    render(<MethodLevelGmv />, {});
    await waitFor(() => {
      expect(screen.queryByText('Total GMV for All Methods in Lakh')).toBeInTheDocument();
    });
  });
  test('should render Method Level Gmv with dots and canvas graph', async () => {
    updateServerResponse();
    const { container } = render(<MethodLevelGmv />, {});
    await waitFor(() => {
      expect(screen.queryByText('Total GMV for All Methods in Lakh')).toBeInTheDocument();
      expect(container.getElementsByClassName('chartjs-render-monitor').length).toBe(1);
    });
  });
});
