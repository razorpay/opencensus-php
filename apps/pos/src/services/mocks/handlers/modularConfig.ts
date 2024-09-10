import { graphql } from 'msw-old';
import { ERROR_MODULAR_RESPONSE, SUCCESS_MODULAR_RESPONSE } from '../fixtures/modularConfig';

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

export const updateModularConfig = ({ type }: { type: string }): any => {
  if (type === 'success') {
    const newConfig = {
      merchantModularOnboardingDetailsUpdateAsSales: {
        ...SUCCESS_MODULAR_RESPONSE.merchantModularOnboardingDetailsAsSales,
      },
    };
    newConfig.merchantModularOnboardingDetailsUpdateAsSales.workflowData.status = 'executed';
    return graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.data(newConfig), ctx.delay(50));
    });
  }
  return graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.data(ERROR_MODULAR_RESPONSE), ctx.delay(50));
  });
};
