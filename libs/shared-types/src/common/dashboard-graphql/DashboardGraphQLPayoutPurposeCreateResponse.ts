import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutPurposeCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutPurposeCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};