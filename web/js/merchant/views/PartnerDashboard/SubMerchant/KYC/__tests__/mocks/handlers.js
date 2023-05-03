import { rest } from 'msw';
import {
  businessCategoriesResponse,
  businessTypesResponse,
  merchantActivationResponse,
} from './fixtures';

export const submerchantKYCHandlers = [
  rest.get('*/merchant/api/test/merchant/activation', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(merchantActivationResponse), ctx.delay(50));
  }),
  rest.get('*/merchant/api/test/merchant/onboarding/business_types', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(businessTypesResponse), ctx.delay(50));
  }),
  rest.get('*/merchant/api/test/merchant/activation/business_categories', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(businessCategoriesResponse), ctx.delay(50));
  }),
];
