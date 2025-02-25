import { DashboardGraphQLMaybe, DashboardGraphQLEmiTenureMap, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLWithdrawalValidity = {
  __typename?: 'DashboardGraphQLWithdrawalValidity';
  emi_tenure_map?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLEmiTenureMap>>>;
  min_valid_tenure?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  withdrawal_possible?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
};