import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLInvoiceItem = {
  __typename?: 'DashboardGraphQLInvoiceItem';
  amount: DashboardGraphQLMoney;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  number?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  quantity?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
};