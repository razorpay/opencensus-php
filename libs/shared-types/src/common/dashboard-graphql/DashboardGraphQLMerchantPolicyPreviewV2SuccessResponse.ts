import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantPolicyPreview } from './index';
export type DashboardGraphQLMerchantPolicyPreviewV2SuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantPolicyPreviewV2SuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  policyPreview: Array<DashboardGraphQLMerchantPolicyPreview>;
  success: DashboardGraphQLScalars['Boolean'];
};