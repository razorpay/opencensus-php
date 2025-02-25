import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantSocialMediaUrlField = {
  __typename?: 'MerchantSocialMediaURLField';
  platform?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  url?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
};