import { Response } from 'express';
import { ShellError } from '../../utils/error-utils';

export const combineUserPayload = (res: Response) => {
  try {
    // Keeping for reference, this object is not used anywhere on the frontend side atleast
    // const appendBankingDetails = () => {
    //   return {
    //     is_test_payout_created: Boolean(res.locals?.merchant_payouts_test?.count),
    //     is_live_payout_created:
    //       res.locals.merchant_details?.activation_details === 'activated' &&
    //       Boolean(res.locals.merchant_payouts_live?.count),
    //   };
    // };

    return {
      ...res.locals.user,
      ...res.locals.merchant_details,
      activated: res.locals.user?.activated,
      role: res.locals.user?.role,
      updated_at: res.locals.user?.updated_at,
      created_at: res.locals.user?.created_at,
      tags: res.locals.merchant_tags,
      splitz_experiments: res.locals.merchant_splitz_experiments,
      experiments: res.locals.merchant_experiments,
      features: res.locals.merchant_features,
      // banking_details: appendBankingDetails(),
    };
  } catch (error) {
    throw new ShellError({
      moduleName: '@combineUserPayload',
      message: `Error combining user payload`,
      statusCode: 500,
    });
  }
};
