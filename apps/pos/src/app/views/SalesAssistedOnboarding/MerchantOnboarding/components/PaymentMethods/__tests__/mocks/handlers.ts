import { graphql } from 'msw';
import { GetMockModularResponseProps, getMockModularResponse } from './fixtures';

interface ModularConfigProps {
  type: string;
  data?: GetMockModularResponseProps;
}

export const getModularConfig = ({ type = 'success', data = {} }: ModularConfigProps): any => {
  if (type === 'failure') {
    const response = {
      merchantModularOnboardingDetailsAsSales: {
        __typename: 'failureResponse',
      },
    };
    return graphql.query('MerchantModularOnboardingDetailsAsSales', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.data(response), ctx.delay(50));
    });
  }
  return graphql.query('MerchantModularOnboardingDetailsAsSales', (_req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.data({
        merchantModularOnboardingDetailsAsSales: {
          __typename: 'merchantModularOnboardingDetailsSuccessResponse',
          workflowData: getMockModularResponse(data),
        },
      }),
      ctx.delay(50),
    );
  });
};

export const updateModularConfig = ({ type = 'success', data = {} }: ModularConfigProps): any => {
  if (type === 'failure') {
    return graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', (_req, res, ctx) => {
      return res(
        ctx.data({
          merchantModularOnboardingDetailsUpdateAsSales: {
            __typename: 'failureResponse',
          },
        }),
      );
    });
  }

  return graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', (_req, res, ctx) => {
    return res(
      ctx.data({
        merchantModularOnboardingDetailsUpdateAsSales: {
          __typename: 'merchantModularOnboardingDetailsSuccessResponse',
          workflowData: getMockModularResponse(data),
        },
      }),
    );
  });
};
