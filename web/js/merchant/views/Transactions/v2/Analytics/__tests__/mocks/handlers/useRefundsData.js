import { server } from 'test-utils';
import { rest } from 'msw';

export const mockServerResponse = ({ kind = 'success' }) => {
  server.use(
    rest.post('*/merchant/api/:mode/merchant/analytics', (req, res, ctx) => {
      if (kind === 'success') {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: {
              refundcountnormal: {
                total: 1,
                last_updated_at: 1681799519,
                result: [
                  {
                    status: 'processed',
                    value: 1,
                  },
                ],
              },
              refundsumnormal: {
                total: 1,
                last_updated_at: 1681799519,
                result: [
                  {
                    status: 'processed',
                    value: 100,
                  },
                ],
              },
              refundcountinstant: {
                last_updated_at: 1681799519,
                result: [],
              },
              refundsuminstant: {
                last_updated_at: 1681799519,
                result: [],
              },
            },
          }),
          ctx.delay(50),
        );
      } else {
        return res(
          ctx.status(400),
          ctx.json({
            status_code: 400,
            success: false,
            data: {
              count: 0,
            },
          }),
          ctx.delay(50),
        );
      }
    }),
  );
};
