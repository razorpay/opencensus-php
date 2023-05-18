import { rest } from 'msw';
import { API_RESPONSE } from './fixtures';

export const fetchAnalyticsData = () => {
  return rest.get('*/merchant/api/test/1cc/analytics', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(API_RESPONSE));
  });
};
