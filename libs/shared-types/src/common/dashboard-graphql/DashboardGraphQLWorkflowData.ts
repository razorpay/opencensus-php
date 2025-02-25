import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLModularOnboardingMilestone } from './index';
export type DashboardGraphQLWorkflowData = {
  __typename?: 'DashboardGraphQLWorkflowData';
  id: DashboardGraphQLScalars['String'];
  milestones?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingMilestone>>>;
  progress: DashboardGraphQLScalars['Float'];
  status: DashboardGraphQLScalars['String'];
};