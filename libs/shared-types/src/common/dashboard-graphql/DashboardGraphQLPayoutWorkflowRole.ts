import { DashboardGraphQLMaybe, DashboardGraphQLPayoutWorkflowRoleChecker, DashboardGraphQLScalars, DashboardGraphQLPayoutWorkflowRoleTypeEnum } from './index';
export type DashboardGraphQLPayoutWorkflowRole = {
  __typename?: 'DashboardGraphQLPayoutWorkflowRole';
  checkers?: DashboardGraphQLMaybe<Array<DashboardGraphQLPayoutWorkflowRoleChecker>>;
  id: DashboardGraphQLScalars['ID'];
  reviewerCount: DashboardGraphQLScalars['Int'];
  type: DashboardGraphQLPayoutWorkflowRoleTypeEnum;
};