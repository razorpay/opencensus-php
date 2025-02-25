import { DashboardGraphQLPaymentLinkAmount, DashboardGraphQLMaybe, DashboardGraphQLCustomer, DashboardGraphQLPaymentLinkDate, DashboardGraphQLScalars, DashboardGraphQLPaymentLinkNotifyBy, DashboardGraphQLPayment, DashboardGraphQLPaymentLinkReminder, DashboardGraphQLPaymentLinkStatusEnum, DashboardGraphQLUser } from './index';
export type DashboardGraphQLPaymentLink = {
  __typename?: 'DashboardGraphQLPaymentLink';
  amount: DashboardGraphQLPaymentLinkAmount;
  customer?: DashboardGraphQLMaybe<DashboardGraphQLCustomer>;
  dates: DashboardGraphQLPaymentLinkDate;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  isPartiallyPayable?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  notifyBy?: DashboardGraphQLMaybe<DashboardGraphQLPaymentLinkNotifyBy>;
  orderId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  payments?: DashboardGraphQLMaybe<Array<DashboardGraphQLPayment>>;
  referenceId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  reminder?: DashboardGraphQLMaybe<DashboardGraphQLPaymentLinkReminder>;
  status: DashboardGraphQLPaymentLinkStatusEnum;
  url: DashboardGraphQLScalars['URL'];
  user?: DashboardGraphQLMaybe<DashboardGraphQLUser>;
};