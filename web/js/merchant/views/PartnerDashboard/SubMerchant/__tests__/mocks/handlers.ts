import { rest } from 'msw';
import {
  emptyAccountsListResponse,
  items,
  productResponse,
  bulkResponse,
  submerchantWithKYCAccess,
} from './fixtures';

export const subMerchantListHandlers = [
  rest.get('*/merchant/api/test/submerchants', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { ...emptyAccountsListResponse, items, count: items.length },
      }),
    );
  }),
  rest.get('*/merchant/api/test/submerchants/:submerchantId', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: submerchantWithKYCAccess,
      }),
    );
  }),

  rest.post(
    '*/merchant/api/*/los/service/twirp/rzp.capital.los.admin.v1.ProductAPI/GetProducts',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: productResponse,
        }),
      );
    },
  ),

  rest.post('*/merchant/api/test/submerchants/capital/applications', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: bulkResponse,
      }),
    );
  }),
];
