import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPolicyEmptyPreviewResponse = {
  __typename?: 'DashboardGraphQLMerchantPolicyEmptyPreviewResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};