import { DashboardGraphQLScalars, DashboardGraphQLMerchantPolicy, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantWebsiteVerificationSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantWebsiteVerificationSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantPolicy: DashboardGraphQLMerchantPolicy;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};