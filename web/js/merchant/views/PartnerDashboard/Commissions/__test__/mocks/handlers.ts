import { rest } from 'msw';
import {
  aggregateDailyData,
  aggregateDetailData,
  balancesListData,
  invoicesListData,
  invoiceDetailData,
  transactionalDetailData,
  transactionalListData,
} from './fixtures';

export const commisionsHandler = [
  rest.get('*/merchant/api/test/commissions_analytics', (req, res, ctx) => {
    const { searchParams } = req.url;
    if (searchParams.get('query_type') === 'aggregate_daily')
      return res(ctx.status(200), ctx.json(aggregateDailyData), ctx.delay(50));
    if (searchParams.get('query_type') === 'aggregate_detail')
      return res(ctx.status(200), ctx.json(aggregateDetailData), ctx.delay(50));
    return res(ctx.status(400), ctx.json({}), ctx.delay(50));
  }),

  rest.get('*/merchant/api/test/commissions/:commisionId', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(transactionalDetailData), ctx.delay(50));
  }),

  rest.get('*/merchant/api/test/commissions/invoice/:commisionId', (req, res, ctx) => {
    return res.once(ctx.status(200), ctx.json(invoiceDetailData), ctx.delay(50));
  }),
  rest.get('*/merchant/api/test/commissions/invoice/fetch/bulk', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(invoicesListData), ctx.delay(50));
  }),

  rest.get('*/merchant/api/test/commissions', (req, res, ctx) => {
    const { searchParams } = req.url;
    if (searchParams.get('model') === 'commission') {
      return res(ctx.status(200), ctx.json(transactionalListData), ctx.delay(50));
    }
    return res(ctx.status(400), ctx.json({}), ctx.delay(50));
  }),
  rest.get('*/merchant/api/test/balances', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(balancesListData), ctx.delay(50));
  }),
];
