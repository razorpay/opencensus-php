import { graphql } from 'msw';
import {
  MERCHANT_ACTIVATION_SUCCESS_RESPONSE,
  MERCHANT_ACTIVATION_ERROR_RESPONSE,
} from '../fixtures/merchantDetails';

export const getMerchantDetails = ({ type }: { type: string }): any => {
  if (type === 'success') {
    return graphql.query('MerchantById', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.data(MERCHANT_ACTIVATION_SUCCESS_RESPONSE), ctx.delay(50));
    });
  }
  return graphql.query('MerchantById', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.data(MERCHANT_ACTIVATION_ERROR_RESPONSE), ctx.delay(50));
  });
};
