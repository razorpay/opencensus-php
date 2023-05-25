import { rest } from 'msw';
import { accountsData } from './fixtures';

export const accountsListSuccess = (response = accountsData) => {
  return rest.get('*/merchant/api/*/linked_accounts', (req, res, ctx) => {
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
