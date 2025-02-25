import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLPhone } from './index';
export type DashboardGraphQLMerchantSupportDetails = {
  __typename?: 'DashboardGraphQLMerchantSupportDetails';
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  phone?: DashboardGraphQLMaybe<DashboardGraphQLPhone>;
  websiteUrl?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
};