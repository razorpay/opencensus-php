import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLUser } from './index';
export type DashboardGraphQLPayoutWorkflowRoleChecker = {
  __typename?: 'DashboardGraphQLPayoutWorkflowRoleChecker';
  approved: DashboardGraphQLScalars['Boolean'];
  comment?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  id: DashboardGraphQLScalars['ID'];
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  user?: DashboardGraphQLMaybe<DashboardGraphQLUser>;
};