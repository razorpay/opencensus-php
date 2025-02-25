import { DashboardGraphQLScalars, DashboardGraphQLWorkflowConfigTemplate } from './index';
export type DashboardGraphQLWorkflowConfig = {
  __typename?: 'DashboardGraphQLWorkflowConfig';
  createdAt: DashboardGraphQLScalars['DateTime'];
  enabled: DashboardGraphQLScalars['Boolean'];
  id: DashboardGraphQLScalars['ID'];
  name: DashboardGraphQLScalars['String'];
  template: DashboardGraphQLWorkflowConfigTemplate;
  type: DashboardGraphQLScalars['String'];
};