import { graphql } from 'msw-old';
import { ERROR_MODULAR_RESPONSE, SUCCESS_MODULAR_RESPONSE } from './fixtures';

export const getModularConfig = ({ type }: { type: string }): any => {
  if (type === 'success') {
    return graphql.query('MerchantModularOnboardingDetailsAsSales', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.data(SUCCESS_MODULAR_RESPONSE));
    });
  }
  return graphql.query('MerchantModularOnboardingDetailsAsSales', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.data(ERROR_MODULAR_RESPONSE), ctx.delay(50));
  });
};
