import { rest } from 'msw';

const transactionDetailsSuccessHandler = (data) =>
  rest.post('*/merchant/api/test/settlements/:id/transaction_source_details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data,
      }),
      ctx.delay(50),
    );
  });

const transactionSourceDetailsErrorHandler = () =>
  rest.post('*/merchant/api/test/settlements/:id/transaction_source_details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: false,
        errors: ['failed to load transaction source details'],
      }),
    );
  });

export { transactionDetailsSuccessHandler, transactionSourceDetailsErrorHandler };
