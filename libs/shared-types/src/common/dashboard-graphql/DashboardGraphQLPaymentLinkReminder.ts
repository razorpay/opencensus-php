import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLPaymentLinkReminderStatusEnum } from './index';
export type DashboardGraphQLPaymentLinkReminder = {
  __typename?: 'DashboardGraphQLPaymentLinkReminder';
  isEnabled?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  status?: DashboardGraphQLMaybe<DashboardGraphQLPaymentLinkReminderStatusEnum>;
};