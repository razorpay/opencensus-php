import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPageItemTaxDetails } from './index';
export type DashboardGraphQLPageItem = {
  __typename?: 'DashboardGraphQLPageItem';
  active: DashboardGraphQLScalars['Boolean'];
  amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  name: DashboardGraphQLScalars['String'];
  taxDetails: DashboardGraphQLPageItemTaxDetails;
  type: DashboardGraphQLScalars['String'];
};