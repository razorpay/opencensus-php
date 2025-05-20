import { graphql } from 'msw-old';
import { getMockModularResponseWithDeviceStep } from './fixtures';

export const getModularConfig = ({ type }: { type: string }): any => {
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
  if (type === 'success') {
    return graphql.query('MerchantModularOnboardingDetailsAsSales', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.data(getMockModularResponseWithDeviceStep({})), ctx.delay(50));
    });
  }
};

export const updateModularConfig = ({ type }: { type: string }): any => {
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
          workflowData:
            getMockModularResponseWithDeviceStep({}).merchantModularOnboardingDetailsUpdateAsSales
              .workflowData,
        },
      }),
    );
  });
};
