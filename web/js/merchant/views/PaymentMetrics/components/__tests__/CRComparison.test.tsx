import React from 'react';
import { render, server, waitFor, screen } from 'test-utils';
import { rest } from 'msw';
import TopSection from 'merchant/views/PaymentMetrics/components/TopSection';
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
                  timestamp: moment().startOf('day').unix(),
                  value: 46.328928046989724,
                },
                {
                  timestamp: moment().subtract(1, 'days').startOf('day').unix(),
                  value: 68.62106406080348,
                },
                {
                  timestamp: moment().subtract(7, 'days').startOf('day').unix(),
                  value: 38.69620253164557,
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
  test('should render Overall Conversion rate as a heading and subheading and LWSD', async () => {
    updateServerResponse();
    render(<TopSection />, {});
    expect(screen.queryByText('Overall Conversion rate')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.queryByText('Last Week Same day')).toBeInTheDocument();
    });
  });

  test('should render Data for Today , Yesterday and LWSD after API call', async () => {
    updateServerResponse();
    render(<TopSection />, {});
    await waitFor(() => {
      expect(screen.queryByText('Yesterday')).toBeInTheDocument();
    });
    expect(screen.queryByText('46 %')).toBeInTheDocument();
    expect(screen.queryByText('69 %')).toBeInTheDocument();
  });
});
