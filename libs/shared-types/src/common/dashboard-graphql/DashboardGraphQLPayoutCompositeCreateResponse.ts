import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPayout } from './index';
export type DashboardGraphQLPayoutCompositeCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutCompositeCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  payout?: DashboardGraphQLMaybe<DashboardGraphQLPayout>;
  success: DashboardGraphQLScalars['Boolean'];
};