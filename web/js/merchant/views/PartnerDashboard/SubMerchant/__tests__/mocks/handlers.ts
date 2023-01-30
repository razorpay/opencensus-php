import { rest } from 'msw';
import { accountsListResponse, items, getByParamsResponse } from './fixtures';

export const subMerchantListHandlers = [
  rest.get('*/merchant/api/test/submerchants', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { ...accountsListResponse, items, count: items.length },
      }),
    );
  }),

  rest.post(
    '*/merchant/api/test/los/service/twirp/rzp.capital.los.origination.v1.ApplicationAPI/GetApplicationsByParam',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: getByParamsResponse,
        }),
      );
    },
  ),
];
