import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPhone = {
  __typename?: 'DashboardGraphQLPhone';
  countryCode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  number?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};