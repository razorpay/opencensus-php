import { DashboardGraphQLPaymentVirtualAccountAmount, DashboardGraphQLMaybe, DashboardGraphQLCustomer, DashboardGraphQLPaymentVirtualAccountDates, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentVirtualAccount = {
  __typename?: 'DashboardGraphQLPaymentVirtualAccount';
  amount: DashboardGraphQLPaymentVirtualAccountAmount;
  customer?: DashboardGraphQLMaybe<DashboardGraphQLCustomer>;
  dates: DashboardGraphQLPaymentVirtualAccountDates;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  name: DashboardGraphQLScalars['String'];
  notes?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>>>;
  status: DashboardGraphQLScalars['String'];
};