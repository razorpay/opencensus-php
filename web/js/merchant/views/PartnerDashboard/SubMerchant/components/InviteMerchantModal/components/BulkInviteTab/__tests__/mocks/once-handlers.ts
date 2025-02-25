import { rest } from 'msw';

import { server } from 'common/services/test/test-utils';
import { fileUploadResponse } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';

export const useValidateBatchErrorHandler = () => {
  server.use(
    rest.post('*/merchant/api/test/batches/validate', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: ['bulk validation error', 'Status Code: 400'],
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const useValidateBatchSuccessHandler = () => {
  server.use(
    rest.post('*/merchant/api/test/batches/validate', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: fileUploadResponse,
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const useCreateBatchSuccessHandler = () => {
  server.use(
    rest.post('*/merchant/api/test/batches', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: { status: 'success' },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const useCreateBatchErrorHandler = () => {
  server.use(
    rest.post('*/merchant/api/test/batches', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          data: ['error'],
        }),
        ctx.delay(50),
      );
    }),
  );
};
