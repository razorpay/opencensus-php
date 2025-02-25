import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQueryWithdrawalValidityArgs = {
  amount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  max_emi_amount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  max_tenure?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  min_tenure?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};