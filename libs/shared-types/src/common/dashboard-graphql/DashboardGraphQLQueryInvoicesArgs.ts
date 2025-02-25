import { DashboardGraphQLInputMaybe, DashboardGraphQLCustomerInput, DashboardGraphQLScalars, DashboardGraphQLInvoiceStatusEnum, DashboardGraphQLInvoiceTypeEnum } from './index';
export type DashboardGraphQLQueryInvoicesArgs = {
  customer?: DashboardGraphQLInputMaybe<DashboardGraphQLCustomerInput>;
  fromDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  receiptNumber?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  status?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLInvoiceStatusEnum>>;
  toDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  types?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLInvoiceTypeEnum>>;
};