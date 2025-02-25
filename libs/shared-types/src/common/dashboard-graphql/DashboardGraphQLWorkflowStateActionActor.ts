import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLWorkflowStateActionActor = {
  __typename?: 'DashboardGraphQLWorkflowStateActionActor';
  comment?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  key: DashboardGraphQLScalars['String'];
  meta?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  type: DashboardGraphQLScalars['String'];
  value: DashboardGraphQLScalars['String'];
};