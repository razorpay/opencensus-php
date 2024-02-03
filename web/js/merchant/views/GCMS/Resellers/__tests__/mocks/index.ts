import { rest } from 'msw';

import { GCMS_BASE_PATH } from 'merchant/views/GCMS/shared/constants';

import { programsListResponse, resellersListResponse } from './fixtures';

export default [
  rest.get(`*/resellers`, (req, res, ctx) => {
    if (req.url.searchParams.get('merchant_name') === 'abc') {
      return res(
        ctx.status(200),
        ctx.json({ ...resellersListResponse, data: { ...resellersListResponse.data, items: [] } }),
        ctx.delay(100),
      );
    }
    if (req.url.searchParams.get('status') === 'approval_pending') {
      return res(
        ctx.status(200),
        ctx.json({ ...resellersListResponse, data: { ...resellersListResponse.data, items: [] } }),
        ctx.delay(100),
      );
    }
    return res(ctx.status(200), ctx.json(resellersListResponse), ctx.delay(100));
  }),
  rest.get(`*${GCMS_BASE_PATH}/skus`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(programsListResponse), ctx.delay(100));
  }),
];
