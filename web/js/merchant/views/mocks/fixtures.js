import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';

export const defaultUser = {
  merchant: {
    fee_bearer: FEE_BEARER_TYPES.PLATFORM,
  },
  findTag: (_x) => false,
  isAllowedView: (_x) => true,
};

export const getInitialReduxState = (user = defaultUser) => ({
  session: {
    user: { ...defaultUser, ...user },
  },
});
