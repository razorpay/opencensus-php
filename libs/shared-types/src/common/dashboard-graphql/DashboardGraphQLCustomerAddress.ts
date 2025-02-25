import { DashboardGraphQLMaybe, DashboardGraphQLAddress } from './index';
export type DashboardGraphQLCustomerAddress = {
  __typename?: 'DashboardGraphQLCustomerAddress';
  billing?: DashboardGraphQLMaybe<DashboardGraphQLAddress>;
  shipping?: DashboardGraphQLMaybe<DashboardGraphQLAddress>;
};