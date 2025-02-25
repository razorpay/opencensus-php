import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaginationResponseInterface = {
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};