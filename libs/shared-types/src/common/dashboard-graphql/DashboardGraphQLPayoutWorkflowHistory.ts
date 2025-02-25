import { DashboardGraphQLScalars, DashboardGraphQLPayoutWorkflowStep } from './index';
export type DashboardGraphQLPayoutWorkflowHistory = {
  __typename?: 'DashboardGraphQLPayoutWorkflowHistory';
  currentLevel: DashboardGraphQLScalars['Int'];
  steps: Array<DashboardGraphQLPayoutWorkflowStep>;
};