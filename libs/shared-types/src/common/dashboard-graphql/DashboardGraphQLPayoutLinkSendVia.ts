import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutLinkSendVia = {
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  sms?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
};