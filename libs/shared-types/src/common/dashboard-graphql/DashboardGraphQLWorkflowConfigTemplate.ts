import { DashboardGraphQLWorkflowConfigState, DashboardGraphQLWorkflowConfigTemplateTypeEnum } from './index';
export type DashboardGraphQLWorkflowConfigTemplate = {
  __typename?: 'DashboardGraphQLWorkflowConfigTemplate';
  states: Array<DashboardGraphQLWorkflowConfigState>;
  type: DashboardGraphQLWorkflowConfigTemplateTypeEnum;
};