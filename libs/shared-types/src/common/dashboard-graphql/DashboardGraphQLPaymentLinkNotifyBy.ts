import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentLinkNotifyBy = {
  __typename?: 'DashboardGraphQLPaymentLinkNotifyBy';
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  sms?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
};