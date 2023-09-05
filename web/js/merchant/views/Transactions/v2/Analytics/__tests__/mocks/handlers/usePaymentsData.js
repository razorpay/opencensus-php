import { server } from 'test-utils';
import { rest } from 'msw';
import { paymentsApiResponse } from 'merchant/views/Transactions/v2/Analytics/__tests__/mocks/fixtures/usePaymentsData';

export const mockServerResponse = ({ kind = 'success', data = paymentsApiResponse }) => {
  server.use(
    rest.post('*/merchant/api/:mode/merchant/analytics', (req, res, ctx) => {
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
