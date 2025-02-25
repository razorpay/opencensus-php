import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantWebsite, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPolicyPublishSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantPolicyPublishSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantWebsite: DashboardGraphQLMerchantWebsite;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};