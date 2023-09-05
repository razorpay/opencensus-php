import { server } from 'test-utils';
import { rest } from 'msw';

export const mockServerResponse = ({
  kind = 'success',
  data = {
    count: 124,
    disputed_amount_sum: 33454,
  },
}) => {
  server.use(
    rest.get('*/merchant/api/:mode/disputes-aggregate', (req, res, ctx) => {
      if (kind === 'success') {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data,
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
