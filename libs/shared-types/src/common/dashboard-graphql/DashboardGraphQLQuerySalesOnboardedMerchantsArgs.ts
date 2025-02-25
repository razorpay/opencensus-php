import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQuerySalesOnboardedMerchantsArgs = {
  endDate: DashboardGraphQLScalars['PositiveInt'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  startDate: DashboardGraphQLScalars['PositiveInt'];
  status: DashboardGraphQLScalars['String'];
};