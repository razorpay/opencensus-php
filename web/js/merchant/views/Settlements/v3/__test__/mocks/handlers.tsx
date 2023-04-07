import { rest } from 'msw';

export const fetchSettlementItem = ({ type }: { type: 'success' | 'failure' }) => {
  return rest.get('/merchant/api/test/settlements/:id', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(
        type === 'success'
          ? {
              status_code: 200,
              success: true,
              data: {
                amount: 23886,
                created_at: 1678077015,
                entity: 'settlement',
                fees: 0,
                id: 'setl_JCVHSjHRi9QHto',
                status: 'processed',
                tax: 0,
                utr: 'cg2mpl08cfbf3p7nghfg',
              },
            }
          : {
              status_code: 400,
              success: false,
              errors: ['JCVHSjHRi9QHto is not a valid id', 'Status Code: 400'],
            },
      ),
      ctx.delay(50),
    );
  });
};

export const fetchSettlementItemServerError = () => {
  return rest.get('/merchant/api/test/settlements/:id', (req, res, ctx) => {
    return res(ctx.status(500), ctx.json({}), ctx.delay(50));
  });
};
