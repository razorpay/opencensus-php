import { rest } from 'msw';
import {
  DB_CATEGORY,
  DB_CATEGORY_WITH_ZONE,
  DB_COUNTRIES,
  DB_FEE_RULE,
  DB_PRODUCTS,
  DB_ZONE,
  DB_ZONE_WITH_FEE,
} from './fixtures';

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

export const fetchSummaryForAdvanced = () => {
  return rest.get('*/merchant/api/:mode/1cc/shipping/cod/summary', (req, res, ctx) => {
    console.log('advanced');
    return res.once(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          configs: {
            cod_engine: true,
            cod_engine_type: 'product',
            shop_id: 'magic-checkout-test-store-1',
            engine: 'Advanced',
            rate_slabs: true,
          },
          fee_rules: [DB_FEE_RULE],
          zones: [DB_ZONE_WITH_FEE],
          item_categories: [DB_CATEGORY_WITH_ZONE],
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
  rest.post('*/merchant/api/:mode/1cc/shipping/cod/item/category', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_CATEGORY,
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
  rest.put('*/merchant/api/:mode/1cc/shipping/cod/item/category', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_CATEGORY,
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
  rest.get(
    '*/merchant/api/:mode/1cc/shipping/cod/item/category/search/products',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            countries: DB_PRODUCTS,
          },
        }),
      );
    },
  ),
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
  rest.put('*/merchant/api/:mode/1cc/shipping/cod/item/category/config', (req, res, ctx) => {
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
