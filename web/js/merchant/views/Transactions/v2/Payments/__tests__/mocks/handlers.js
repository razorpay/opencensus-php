import { rest } from 'msw';
import { server } from 'test-utils';

export const mockPaymentAPIResponse = ({ count, error }) => {
  return server.use(
    rest.get('*/merchant/api/:mode/payments', (req, res, ctx) => {
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

export const mockStoreHierarchyAPIResponse = ({ error }) => {
  return server.use(
    rest.get('*/merchant/api/:mode/store_management_service/store_hierarchy', (req, res, ctx) => {
      if (error) {
        return res(ctx.errors([error]), ctx.delay(50));
      }
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: { store_hierarchy: [] },
        }),
        ctx.delay(50),
      );
    }),
  );
};
