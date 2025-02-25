import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPayoutLink } from './index';
export type DashboardGraphQLPayoutLinkCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutLinkCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  payoutLink?: DashboardGraphQLMaybe<DashboardGraphQLPayoutLink>;
  success: DashboardGraphQLScalars['Boolean'];
};