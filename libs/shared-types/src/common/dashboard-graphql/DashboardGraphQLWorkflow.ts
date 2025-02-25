import { DashboardGraphQLWorkflowConfig, DashboardGraphQLWorkflowCreator, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLWorkflowState, DashboardGraphQLWorkflowStatusEnum } from './index';
export type DashboardGraphQLWorkflow = {
  __typename?: 'DashboardGraphQLWorkflow';
  config: DashboardGraphQLWorkflowConfig;
  creator: DashboardGraphQLWorkflowCreator;
  id: DashboardGraphQLScalars['ID'];
  states?: DashboardGraphQLMaybe<Array<DashboardGraphQLWorkflowState>>;
  status: DashboardGraphQLWorkflowStatusEnum;
};