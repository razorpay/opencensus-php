import { rest } from 'msw';
import { DB_COUNTRIES, DB_FEE_RULE, DB_ZONE } from './fixtures';

export const fetchSummaryWithoutSlabsAndZones = () => {
  return rest.get('*/merchant/api/:mode/1cc/shipping/cod/summary', (req, res, ctx) => {
    return res.once(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          configs: {
            cod_engine: true,
            cod_engine_type: 'slab_charges',
            shop_id: 'magic-checkout-test-store-1',
            engine: 'Basic',
            rate_slabs: true,
          },
          fee_rules: [],
          zones: [],
        },
      }),
    );
  });
};

export const codEngineHandlers = [
  rest.get('*/merchant/api/:mode/1cc/shipping/cod/summary', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          configs: {
            cod_engine: true,
            cod_engine_type: 'slab_charges',
            shop_id: 'magic-checkout-test-store-1',
            engine: 'Basic',
            rate_slabs: true,
          },
          fee_rules: [DB_FEE_RULE],
          zones: [DB_ZONE],
        },
      }),
    );
  }),
  rest.post('*/merchant/api/:mode/1cc/shipping/cod/fee_rules', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          fee_rules: [DB_FEE_RULE],
        },
      }),
    );
  }),
  rest.post('*/merchant/api/:mode/1cc/shipping/cod/zone', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_ZONE,
      }),
    );
  }),
  rest.put('*/merchant/api/:mode/1cc/shipping/cod/zone', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_ZONE,
      }),
    );
  }),
  rest.get('*/merchant/api/:mode/1cc/shipping/cod/countries', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          countries: DB_COUNTRIES,
        },
      }),
    );
  }),
  rest.post('*/merchant/api/:mode/1cc/merchant/configs', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: [],
      }),
    );
  }),
];
