import { DashboardGraphQLScalars, DashboardGraphQLMerchantWebsite, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantWebsiteDetailsResponse = {
  __typename?: 'DashboardGraphQLMerchantWebsiteDetailsResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantWebsite: DashboardGraphQLMerchantWebsite;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};