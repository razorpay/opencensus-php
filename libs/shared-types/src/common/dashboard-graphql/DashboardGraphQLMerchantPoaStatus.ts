import { DashboardGraphQLMaybe, DashboardGraphQLMerchantPoaVerificationErrorCodeEnum, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantPoaStatus = {
  __typename?: 'DashboardGraphQLMerchantPoaStatus';
  poaVerificationErrorCode?: DashboardGraphQLMaybe<DashboardGraphQLMerchantPoaVerificationErrorCodeEnum>;
  poaVerificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};