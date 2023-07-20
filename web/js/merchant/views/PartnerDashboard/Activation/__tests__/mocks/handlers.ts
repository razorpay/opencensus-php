import { rest } from 'msw';
import { partnerActivationResponse } from './fixtures';

export const partnerActivationHandler = [
  rest.post('*/merchant/api/*/partner/activation', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(partnerActivationResponse), ctx.delay(50));
  }),
  rest.get('*/merchant/api/*/partner/activation', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(partnerActivationResponse), ctx.delay(50));
  }),
];
