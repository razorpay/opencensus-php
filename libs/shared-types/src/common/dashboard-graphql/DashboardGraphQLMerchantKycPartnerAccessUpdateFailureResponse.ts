import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantKycPartnerAccessErrorTypeEnum } from './index';
export type DashboardGraphQLMerchantKycPartnerAccessUpdateFailureResponse = {
  __typename?: 'MerchantKYCPartnerAccessUpdateFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLMerchantKycPartnerAccessErrorTypeEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};