import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQueryPaymentPagesArgs = {
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};