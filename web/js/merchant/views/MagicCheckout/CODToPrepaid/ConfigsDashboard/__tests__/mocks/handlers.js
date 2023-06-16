import { rest } from 'msw';

export const fetchPrepayConfigs = (payload) => {
  return rest.get('*/merchant/api/test/1cc/prepay/configs', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(payload), ctx.delay(50));
  });
};
