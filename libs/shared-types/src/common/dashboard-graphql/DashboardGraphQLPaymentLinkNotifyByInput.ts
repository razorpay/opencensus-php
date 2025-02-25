import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentLinkNotifyByInput = {
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  sms?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
};