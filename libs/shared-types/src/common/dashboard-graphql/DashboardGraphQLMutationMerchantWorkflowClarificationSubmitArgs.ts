import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe, DashboardGraphQLMerchantSelfServeWorkflowEnum } from './index';
export type DashboardGraphQLMutationMerchantWorkflowClarificationSubmitArgs = {
  clarificationReason: DashboardGraphQLScalars['String'];
  documentIds: Array<DashboardGraphQLInputMaybe<DashboardGraphQLScalars['ID']>>;
  workflow: DashboardGraphQLMerchantSelfServeWorkflowEnum;
};