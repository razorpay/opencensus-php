import { rest } from 'msw';
import {
  MERCHANT_ID_REGISTER_ERROR,
  MERCHANT_ID_REGISTER_SUCCESS,
  MERCHANT_OTP_VERIFY_DUPLICATE_USER_ERROR,
  MERCHANT_OTP_VERIFY_ERROR,
  MERCHANT_OTP_VERIFY_SUCCESS,
} from '../fixtures/merchantRegistration';

export const merchantRegisterHandler = ({ type }: { type: string }) => {
  if (type === 'success') {
    return rest.post('*/user/register/otp', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(MERCHANT_OTP_VERIFY_SUCCESS), ctx.delay(50));
    });
  } else if (type === 'duplicateUser') {
    return rest.post('*/user/register/otp', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json(MERCHANT_OTP_VERIFY_DUPLICATE_USER_ERROR),
        ctx.delay(50),
      );
    });
  }
  return rest.post('*/user/register/otp', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.json(MERCHANT_OTP_VERIFY_ERROR), ctx.delay(50));
  });
};

export const merchantOtpVerifyHandler = ({ type }: { type: string }) => {
  if (type === 'success') {
    return rest.post('*/register/merchant/otp/verify', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(MERCHANT_ID_REGISTER_SUCCESS), ctx.delay(50));
    });
  }
  return rest.post('*/register/merchant/otp/verify', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.json(MERCHANT_ID_REGISTER_ERROR), ctx.delay(50));
  });
};
