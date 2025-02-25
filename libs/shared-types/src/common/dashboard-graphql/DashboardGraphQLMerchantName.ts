import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantName = {
  __typename?: 'DashboardGraphQLMerchantName';
  billing?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  display?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  registered?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};