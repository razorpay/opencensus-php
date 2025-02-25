import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPayout } from './index';
export type DashboardGraphQLPayoutCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  payout?: DashboardGraphQLMaybe<DashboardGraphQLPayout>;
  success: DashboardGraphQLScalars['Boolean'];
};