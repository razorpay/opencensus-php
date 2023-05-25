import { rest } from 'msw';
import { reversalsData } from './fixtures';

export const reversalsListSuccess = (response = reversalsData) => {
  return rest.get('*/merchant/api/*/reversals', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: response,
      }),
      ctx.delay(50),
    );
  });
};
