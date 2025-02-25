import { DashboardGraphQLWorkflowStateActionActor, DashboardGraphQLScalars, DashboardGraphQLWorkflowStateActionStatusEnum } from './index';
export type DashboardGraphQLWorkflowStateAction = {
  __typename?: 'DashboardGraphQLWorkflowStateAction';
  actor: DashboardGraphQLWorkflowStateActionActor;
  createdAt: DashboardGraphQLScalars['DateTime'];
  id: DashboardGraphQLScalars['ID'];
  status: DashboardGraphQLWorkflowStateActionStatusEnum;
  type: DashboardGraphQLScalars['String'];
};