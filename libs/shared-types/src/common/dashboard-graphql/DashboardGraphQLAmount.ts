import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLAmount = {
  __typename?: 'DashboardGraphQLAmount';
  interest?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  principal?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  total_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};