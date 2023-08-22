import React from 'react';
import { render, server, waitFor, screen } from 'test-utils';
import { rest } from 'msw';
import moment from 'moment';
import IndustryLevelOverallCr from 'merchant/views/PaymentMetrics/components/IndustryLevelOverallCr';

const updateServerResponse = () => {
  server.use(
    rest.post('*/live/merchant/analytics', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          data: {
            checkout_industry_level_cr: {
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

describe('CR Comparison for Indutry Level', () => {
  test('should render Industry Level Overall Conversion rate as component with heading', async () => {
    updateServerResponse();
    render(<IndustryLevelOverallCr />, { category: 'others' });
    await waitFor(() => {
      expect(screen.queryByText('Industry Level Conversion Rate')).toBeInTheDocument();
    });
  });
  test('should render Overall Conversion rate with dots and canvas graph', async () => {
    updateServerResponse();
    const { container } = render(<IndustryLevelOverallCr />, { category: 'others' });
    await waitFor(() => {
      expect(screen.queryByText('Industry Level Conversion Rate')).toBeInTheDocument();
      expect(container.getElementsByClassName('chartjs-render-monitor').length).toBe(1);
    });
  });
});
