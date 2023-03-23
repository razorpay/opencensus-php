import { rest } from 'msw';
import { listBatchesResponse } from './fixtures';

export default [
  rest.get('/merchant/api/test/batches', (req, res, ctx) => {
    const batchType = req.url.searchParams.get('types[0]');

    /* Empty array needs to be send in case batchType is not related to wallet */
    const json =
      batchType === 'create_wallet_accounts'
        ? listBatchesResponse
        : {
            status_code: 200,
            success: true,
            data: {
              count: 0,
              entity: 'collection',
              items: [],
            },
          };
    return res(ctx.status(200), ctx.json(json), ctx.delay(1));
  }),
];
