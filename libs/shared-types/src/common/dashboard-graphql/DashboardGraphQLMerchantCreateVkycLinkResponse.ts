import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantCreateVkycLinkResponse = {
  __typename?: 'DashboardGraphQLMerchantCreateVkycLinkResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  webLink?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
};