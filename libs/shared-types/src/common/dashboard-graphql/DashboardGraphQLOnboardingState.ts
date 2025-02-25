import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLOnboardingState = {
  __typename?: 'DashboardGraphQLOnboardingState';
  milestones: Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>>;
  modularComponents: Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>>;
  steps: Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>>;
};