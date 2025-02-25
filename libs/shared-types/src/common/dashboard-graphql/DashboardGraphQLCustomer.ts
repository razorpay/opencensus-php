import { DashboardGraphQLMaybe, DashboardGraphQLCustomerAddress, DashboardGraphQLScalars, DashboardGraphQLPhone } from './index';
export type DashboardGraphQLCustomer = {
  __typename?: 'DashboardGraphQLCustomer';
  address?: DashboardGraphQLMaybe<DashboardGraphQLCustomerAddress>;
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  id?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  phone?: DashboardGraphQLMaybe<DashboardGraphQLPhone>;
};