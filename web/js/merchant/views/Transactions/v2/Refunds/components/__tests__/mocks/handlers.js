import { rest } from 'msw';
import { server } from 'test-utils';

export const mockRefundAPIResponse = ({ count, error }) => {
  return server.use(
    rest.get('*/merchant/api/:mode/refunds', (req, res, ctx) => {
      if (error) {
        return res(ctx.errors([error]), ctx.delay(50));
      }
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: { count },
        }),
        ctx.delay(50),
      );
    }),
  );
};
