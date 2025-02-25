import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPaymentRefundStatusEnum } from './index';
export type DashboardGraphQLQueryRefundsArgs = {
  fromDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  paymentId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['ID']>;
  status?: DashboardGraphQLInputMaybe<DashboardGraphQLPaymentRefundStatusEnum>;
  toDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
};