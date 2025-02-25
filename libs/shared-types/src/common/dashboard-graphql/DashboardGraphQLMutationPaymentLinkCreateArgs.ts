import { DashboardGraphQLMoneyInput, DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLCustomerInput, DashboardGraphQLPaymentLinkNotifyByInput } from './index';
export type DashboardGraphQLMutationPaymentLinkCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  autoReminder?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  customer?: DashboardGraphQLInputMaybe<DashboardGraphQLCustomerInput>;
  description?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  expireBy?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  firstMinimumPartialAmount?: DashboardGraphQLInputMaybe<DashboardGraphQLMoneyInput>;
  isPartiallyPayable?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  notifyBy?: DashboardGraphQLInputMaybe<DashboardGraphQLPaymentLinkNotifyByInput>;
  referenceId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};