import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLConfiguration } from './index';
export type DashboardGraphQLWithdrawalConfig = {
  __typename?: 'DashboardGraphQLWithdrawalConfig';
  application_id?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  application_number?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  configuration: DashboardGraphQLConfiguration;
  effective_balance?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  max_emi?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  owner_id?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  owner_type?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  principal_outstanding_balance?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  product_type?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  repayment_frequency?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  status?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};