import { DashboardGraphQLScalars, DashboardGraphQLWorkflowCreatorTypeEnum } from './index';
export type DashboardGraphQLWorkflowCreator = {
  __typename?: 'DashboardGraphQLWorkflowCreator';
  id: DashboardGraphQLScalars['ID'];
  type: DashboardGraphQLWorkflowCreatorTypeEnum;
};