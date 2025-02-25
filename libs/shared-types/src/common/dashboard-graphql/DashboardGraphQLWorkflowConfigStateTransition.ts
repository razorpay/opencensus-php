import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLWorkflowConfigStateTransition = {
  __typename?: 'DashboardGraphQLWorkflowConfigStateTransition';
  isEndState: DashboardGraphQLScalars['Boolean'];
  isStartState: DashboardGraphQLScalars['Boolean'];
  nextStates: Array<DashboardGraphQLScalars['String']>;
};