import { ResponseResolver, MockedRequest, rest } from 'msw';
import {
  emptyInvoicesListData,
  invoicesListData,
  invoiceDetailDataCurlec,
  transactionalDetailDataCurlec,
  transactionalListDataCurlec,
} from './fixtures';
import { server } from 'common/services/test/test-utils';

export const mockInvoicesFetchBulkRequestHandler = (
  expectHandler: ResponseResolver<MockedRequest>,
): void => {
  server.use(
    rest.get('*/merchant/api/test/commissions/invoice/fetch/bulk', (req, res, ctx) => {
      expectHandler(req, res, ctx);
      return res.once(ctx.status(200), ctx.json(invoicesListData), ctx.delay(50));
    }),
  );
};

export const mockInvoicesFetchBulkEmpty = (): void => {
  server.use(
    rest.get('*/merchant/api/test/commissions/invoice/fetch/bulk', (req, res, ctx) => {
      return res.once(ctx.status(200), ctx.json(emptyInvoicesListData), ctx.delay(50));
    }),
  );
};

export const mockCommisionsListOnceForCurlec = (): void =>
  server.use(
    rest.get('*/merchant/api/test/commissions', (req, res, ctx) => {
      const { searchParams } = req.url;
      if (searchParams.get('model') === 'commission') {
        return res.once(ctx.status(200), ctx.json(transactionalListDataCurlec), ctx.delay(50));
      }
      return res.once(ctx.status(400), ctx.json({}), ctx.delay(50));
    }),
  );

export const mockCommissionDetailOnceForCurlec = (): void =>
  server.use(
    rest.get('*/merchant/api/test/commissions/:commisionId', (req, res, ctx) => {
      return res.once(ctx.status(200), ctx.json(transactionalDetailDataCurlec), ctx.delay(50));
    }),
  );

export const mockInvoiceDetailOnceForCurlec = (): void =>
  server.use(
    rest.get('*/merchant/api/test/commissions/invoice/:commisionId', (req, res, ctx) => {
      return res.once(ctx.status(200), ctx.json(invoiceDetailDataCurlec), ctx.delay(50));
    }),
  );
