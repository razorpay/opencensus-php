import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantWebsite, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantWebsitePublishSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantWebsitePublishSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantWebsite: DashboardGraphQLMerchantWebsite;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};