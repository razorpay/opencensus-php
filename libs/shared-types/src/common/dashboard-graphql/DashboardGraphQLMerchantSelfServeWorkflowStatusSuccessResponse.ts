import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantSelfServeWorkflow } from './index';
export type DashboardGraphQLMerchantSelfServeWorkflowStatusSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantSelfServeWorkflowStatusSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  selfServeWorkflow: DashboardGraphQLMerchantSelfServeWorkflow;
  success: DashboardGraphQLScalars['Boolean'];
};