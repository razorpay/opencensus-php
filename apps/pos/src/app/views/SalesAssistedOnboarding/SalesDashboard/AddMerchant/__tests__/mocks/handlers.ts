import { rest } from 'msw';
import {
  MERCHANT_ID_REGISTER_ERROR,
  MERCHANT_ID_REGISTER_SUCCESS,
  MERCHANT_OTP_VERIFY_DUPLICATE_USER_ERROR,
  MERCHANT_OTP_VERIFY_ERROR,
  MERCHANT_OTP_VERIFY_SUCCESS,
  MERCHANT_SWITCH_SUCCESS_ERROR,
  MERCHANT_SWITCH_SUCCESS_RESPONSE,
} from './fixtures';

export const getMerchantVerifyResponse = ({ type }: { type: string }) => {
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

export const getSwitchMerchantResponse = ({ type }: { type: string }) => {
  if (type === 'success') {
    return rest.get('*/settings/merchants/switch/*', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(MERCHANT_SWITCH_SUCCESS_RESPONSE), ctx.delay(50));
    });
  }
  return rest.get('*/settings/merchants/switch/*', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.json(MERCHANT_SWITCH_SUCCESS_ERROR), ctx.delay(50));
  });
};

export const getMerchantRegister = ({ type }: { type: string }) => {
  if (type === 'success') {
    return rest.post('*/register/merchant/otp/verify', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(MERCHANT_ID_REGISTER_SUCCESS), ctx.delay(50));
    });
  }
  return rest.post('*/register/merchant/otp/verify', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.json(MERCHANT_ID_REGISTER_ERROR), ctx.delay(50));
  });
};
