import { DashboardGraphQLInputMaybe, DashboardGraphQLCustomerInput, DashboardGraphQLScalars, DashboardGraphQLPaymentLinkStatusEnum } from './index';
export type DashboardGraphQLQueryPaymentLinksArgs = {
  customer?: DashboardGraphQLInputMaybe<DashboardGraphQLCustomerInput>;
  fromDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  referenceId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  status?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLPaymentLinkStatusEnum>>;
  toDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
};