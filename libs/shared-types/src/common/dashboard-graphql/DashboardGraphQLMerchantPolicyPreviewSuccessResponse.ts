import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPolicyPreviewSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantPolicyPreviewSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  policyPreview: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};