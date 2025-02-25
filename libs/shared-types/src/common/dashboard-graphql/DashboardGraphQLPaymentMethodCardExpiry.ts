import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentMethodCardExpiry = {
  __typename?: 'DashboardGraphQLPaymentMethodCardExpiry';
  month?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  year?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
};