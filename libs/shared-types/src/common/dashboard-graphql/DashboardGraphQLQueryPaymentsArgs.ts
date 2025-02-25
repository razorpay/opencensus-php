import { DashboardGraphQLInputMaybe, DashboardGraphQLCustomerInput, DashboardGraphQLScalars, DashboardGraphQLPaymentMethodEnum, DashboardGraphQLPaymentStatusEnum } from './index';
export type DashboardGraphQLQueryPaymentsArgs = {
  customer?: DashboardGraphQLInputMaybe<DashboardGraphQLCustomerInput>;
  fromDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  method?: DashboardGraphQLInputMaybe<DashboardGraphQLPaymentMethodEnum>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  status?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLPaymentStatusEnum>>;
  toDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
};