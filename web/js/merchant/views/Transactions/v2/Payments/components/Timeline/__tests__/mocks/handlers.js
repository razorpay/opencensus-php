import { rest } from 'msw';
import { server } from 'test-utils';

export const mockPaymentCapture = () => {
  return server.use(
    rest.post('*/merchant/api/:mode/payments/:id/capture', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {},
        }),
        ctx.delay(50),
      );
    }),
  );
};
