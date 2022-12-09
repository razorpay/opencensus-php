import { rest } from 'msw';

export const settlementInfoErrorHandler = () =>
  rest.get('*/merchant/api/:mode/settlements/:id/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: false,
        errors: 'Something went wrong',
      }),
      ctx.delay(50),
    );
  });

export const fetchSettlementErrorHandler = () =>
  // Settlements
  rest.get('*/merchant/api/test/settlements/:id', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: false,
        errors: 'failed while fetching settlement',
      }),
      ctx.delay(50),
    );
  });

export const fetchBankSettlementStatusHandler = (
  response = { success: true, data: { org_settlement: { status: 'SETTLED' } } },
) =>
  rest.get('*/merchant/api/:mode/org_settlements/:id', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(response), ctx.delay(50));
  });

export const fetchIsAdminAsMerchantHandler = (
  response = { success: true, data: { is_admin_as_merchant: true } },
) =>
  rest.get('*/merchant/api/:mode/merchant/is_admin_as_merchant', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(response), ctx.delay(50));
  });
