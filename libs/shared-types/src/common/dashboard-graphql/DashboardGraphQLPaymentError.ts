import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentError = {
  __typename?: 'DashboardGraphQLPaymentError';
  code?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};