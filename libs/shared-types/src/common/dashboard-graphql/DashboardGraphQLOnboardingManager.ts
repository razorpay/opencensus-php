import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLOnboardingManager = {
  __typename?: 'DashboardGraphQLOnboardingManager';
  activationTime?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['BigInt']>;
  managerAssignmentTime?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
};