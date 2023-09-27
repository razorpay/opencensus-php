import { rest } from 'msw';

import {
  DB_CATEGORY,
  DB_COUNTRIES,
  DB_METHOD,
  DB_PRODUCTS,
  DB_PROFILES,
  DB_ZONE,
} from './fixtures';

export const magicShippingEngineHandlers = [
  rest.get('*/merchant/api/:mode/1cc/shipping/profiles', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_PROFILES,
      }),
    );
  }),
  rest.get('*/merchant/api/:mode/1cc/shipping/countries', (req, res, ctx) => {
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
  rest.post('*/merchant/api/:mode/1cc/shipping/zones', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_ZONE,
      }),
    );
  }),
  rest.post('*/merchant/api/:mode/1cc/shipping/methods', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_METHOD,
      }),
    );
  }),

  rest.put('*/merchant/api/:mode/1cc/shipping/zones/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_ZONE,
      }),
    );
  }),

  rest.get('*/merchant/api/:mode/1cc/shipping/zones/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_ZONE,
      }),
    );
  }),

  rest.put('*/merchant/api/:mode/1cc/shipping/methods/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_METHOD,
      }),
    );
  }),

  rest.post('*/merchant/api/:mode/1cc/shipping/item/category', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_CATEGORY,
      }),
    );
  }),

  rest.put('*/merchant/api/:mode/1cc/shipping/item/category/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_CATEGORY,
      }),
    );
  }),

  rest.get('*/merchant/api/:mode/1cc/shipping/item/category/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: DB_CATEGORY,
      }),
    );
  }),

  rest.get('*/merchant/api/:mode/1cc/shipping/item/category/search/products', (req, res, ctx) => {
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
