import { rest } from 'msw';

import { posSubmerchantDetailsResponse } from './fixtures';

export const posSubmerchantDetailsSuccess: any = (response = posSubmerchantDetailsResponse) => {
  return rest.get('*/merchant/api/test/submerchants/:submerchantId', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(response));
  });
};
