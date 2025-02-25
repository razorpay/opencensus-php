import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLEmiTenureMap = {
  __typename?: 'DashboardGraphQLEmiTenureMap';
  emi_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tenure?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};