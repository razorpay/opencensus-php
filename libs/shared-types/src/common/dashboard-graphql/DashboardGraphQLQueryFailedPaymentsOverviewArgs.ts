import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQueryFailedPaymentsOverviewArgs = {
  entity: DashboardGraphQLScalars['String'];
  fromDate: DashboardGraphQLScalars['DateTime'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  toDate: DashboardGraphQLScalars['DateTime'];
};