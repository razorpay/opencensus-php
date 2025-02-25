import { DashboardGraphQLMaybe, DashboardGraphQLWorkflowStateAction, DashboardGraphQLWorkflowStateDates, DashboardGraphQLScalars, DashboardGraphQLWorkflowConfigStateRulePayout, DashboardGraphQLWorkflowStateStatusEnum, DashboardGraphQLWorkflowConfigStateTypeEnum } from './index';
export type DashboardGraphQLWorkflowState = {
  __typename?: 'DashboardGraphQLWorkflowState';
  actions?: DashboardGraphQLMaybe<Array<DashboardGraphQLWorkflowStateAction>>;
  dates: DashboardGraphQLWorkflowStateDates;
  id: DashboardGraphQLScalars['ID'];
  name: DashboardGraphQLScalars['String'];
  rule: DashboardGraphQLWorkflowConfigStateRulePayout;
  status: DashboardGraphQLWorkflowStateStatusEnum;
  type: DashboardGraphQLWorkflowConfigStateTypeEnum;
};