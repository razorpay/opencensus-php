import { rest } from 'msw';

import { createPaymentLinkResponse, eligibilityResponse, merchantMethodsResponse } from './api';

export default [
  rest.get(`*/merchant/methods`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(merchantMethodsResponse), ctx.delay(100));
  }),
  rest.post(`*/merchant/customers/eligibility`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(eligibilityResponse), ctx.delay(100));
  }),
  rest.post(`*/payment_links`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(createPaymentLinkResponse), ctx.delay(100));
  }),
];
