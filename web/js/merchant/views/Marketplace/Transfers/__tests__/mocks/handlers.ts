import { rest } from 'msw';
import { transfersData } from './fixtures';

export const transfersListSuccess = (response = transfersData) => {
  return rest.get('*/merchant/api/*/transfers', (req, res, ctx) => {
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
