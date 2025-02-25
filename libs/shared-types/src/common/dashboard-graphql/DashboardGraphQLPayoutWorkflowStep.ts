import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPayoutWorkflowStepOperationTypeEnum, DashboardGraphQLPayoutWorkflowRole } from './index';
export type DashboardGraphQLPayoutWorkflowStep = {
  __typename?: 'DashboardGraphQLPayoutWorkflowStep';
  id: DashboardGraphQLScalars['ID'];
  level: DashboardGraphQLScalars['PositiveInt'];
  operationType?: DashboardGraphQLMaybe<DashboardGraphQLPayoutWorkflowStepOperationTypeEnum>;
  roles: Array<DashboardGraphQLPayoutWorkflowRole>;
};