import { rest } from 'msw';
import {
  mobileNumberResponse,
  mobileNumberVerificationResponse,
  userWhatsappOptInResponse,
} from './fixtures';

export const newAuthHandler = [
  rest.post('*/user/register/otp', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(mobileNumberResponse), ctx.delay(50));
  }),
  rest.post('*/user/register/otp/verify', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(mobileNumberVerificationResponse), ctx.delay(50));
  }),
  rest.post('*/user/whatsapp/opt_in', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(userWhatsappOptInResponse), ctx.delay(50));
  }),
];
