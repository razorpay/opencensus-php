import { DashboardGraphQLScalars, DashboardGraphQLWorkflowConfigStateRulePayout, DashboardGraphQLWorkflowConfigStateTransition, DashboardGraphQLMaybe, DashboardGraphQLWorkflowConfigStateTypeEnum } from './index';
export type DashboardGraphQLWorkflowConfigState = {
  __typename?: 'DashboardGraphQLWorkflowConfigState';
  name: DashboardGraphQLScalars['String'];
  rule: DashboardGraphQLWorkflowConfigStateRulePayout;
  transition: DashboardGraphQLWorkflowConfigStateTransition;
  type?: DashboardGraphQLMaybe<DashboardGraphQLWorkflowConfigStateTypeEnum>;
};