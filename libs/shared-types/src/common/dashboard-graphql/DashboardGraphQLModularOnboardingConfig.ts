import { DashboardGraphQLMaybe, DashboardGraphQLModularOnboardingMilestone } from './index';
export type DashboardGraphQLModularOnboardingConfig = {
  __typename?: 'DashboardGraphQLModularOnboardingConfig';
  milestones?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingMilestone>>>;
};