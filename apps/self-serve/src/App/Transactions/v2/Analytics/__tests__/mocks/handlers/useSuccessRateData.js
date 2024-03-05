import { rest } from 'msw';
import { server } from 'apps/self-serve/src/services/test/test-utils';

export const mockServerResponse = ({
  kind = 'success',
  data = {
    sr: 99.99,
  },
}) => {
  server.use(
    rest.post('*/success-rate/merchant/sr', (req, res, ctx) => {
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
            data,
          }),
          ctx.delay(50),
        );
      }
    }),
  );
};
