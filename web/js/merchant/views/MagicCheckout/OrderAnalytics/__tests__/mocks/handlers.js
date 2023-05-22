import { rest } from 'msw';
import { API_RESPONSE } from './fixtures';

export const magicOrderAnalyticsHandler = [
  rest.get('*/merchant/api/test/1cc/analytics', (req, res, ctx) =>
    res(ctx.status(200), ctx.json(API_RESPONSE)),
  ),
];
