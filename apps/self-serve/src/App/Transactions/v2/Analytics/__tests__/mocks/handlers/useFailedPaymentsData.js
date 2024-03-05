import { rest } from 'msw';
import { server } from 'apps/self-serve/src/services/test/test-utils';
import { apiResponse } from 'apps/self-serve/src/App/Transactions/v2/Analytics/__tests__/mocks/fixtures/useFailedPaymentsData';

export const mockServerResponse = ({ kind = 'success', data = apiResponse }) => {
  server.use(
    rest.post('*/merchant/api/:mode/success-rate/merchant/error', (req, res, ctx) => {
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
            data: {},
          }),
          ctx.delay(50),
        );
      }
    }),
  );
};
