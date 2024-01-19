import { rest } from 'msw';
export const fetchAllowlist = (payload) => {
  return rest.get('*/merchant/api/test/1cc/shipping/cod/allowlist', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(payload), ctx.delay(50));
  });
};

export const uploadAllowlist = (payload) => {
  return rest.post('*/merchant/api/test/1cc/shipping/cod/allowlist', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(payload), ctx.delay(50));
  });
};

export const deleteAllowlist = () => {
  return rest.delete('*/merchant/api/test/1cc/shipping/cod/allowlist', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json({}), ctx.delay(50));
  });
};

export const downloadAllowlist = () => {
  return rest.get('*/merchant/api/test/1cc/shipping/cod/allowlist/download', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json({}), ctx.delay(50));
  });
};
