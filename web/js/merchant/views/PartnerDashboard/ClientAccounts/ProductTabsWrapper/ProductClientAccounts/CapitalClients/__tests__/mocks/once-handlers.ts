import { rest } from 'msw';

import {
  losProductsResponse,
  capitalApplicationsResponse,
  createBureauLinkResponse,
} from './fixtures';

export const losProductsListHandler = (data = losProductsResponse) =>
  rest.post(
    '*/merchant/api/*/los/service/twirp/rzp.capital.los.admin.v1.ProductAPI/GetProducts',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data,
        }),
      );
    },
  );
export const capitalApplicationsHandler = (data = capitalApplicationsResponse) =>
  rest.post('*/merchant/api/*/submerchants/capital/applications', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
    );
  });

export const capitalApplicationsErrorHandler = () =>
  rest.post('*/merchant/api/*/submerchants/capital/applications', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        data: ['something went wrong!'],
      }),
      ctx.delay(50),
    );
  });

export const createBureauLinkSuccess = (data = createBureauLinkResponse) => {
  return rest.post(
    '*/partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/GenerateBureauLink',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data,
        }),
        ctx.delay(50),
      );
    },
  );
};

export const createBureauLinkError = () => {
  return rest.post(
    '*/partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/GenerateBureauLink',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: ['There was an error', 'Status Code: 400'],
        }),
        ctx.delay(50),
      );
    },
  );
};
