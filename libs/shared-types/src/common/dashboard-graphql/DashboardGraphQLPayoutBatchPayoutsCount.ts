import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutBatchPayoutsCount = {
  __typename?: 'DashboardGraphQLPayoutBatchPayoutsCount';
  failure: DashboardGraphQLScalars['NonNegativeInt'];
  processed: DashboardGraphQLScalars['NonNegativeInt'];
  success: DashboardGraphQLScalars['NonNegativeInt'];
  total: DashboardGraphQLScalars['NonNegativeInt'];
  validated: DashboardGraphQLScalars['NonNegativeInt'];
};