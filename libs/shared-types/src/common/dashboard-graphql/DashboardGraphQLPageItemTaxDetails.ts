import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPageItemTaxDetails = {
  __typename?: 'DashboardGraphQLPageItemTaxDetails';
  sacCode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  taxGroupId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  taxId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  taxInclusive?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  taxRate?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};