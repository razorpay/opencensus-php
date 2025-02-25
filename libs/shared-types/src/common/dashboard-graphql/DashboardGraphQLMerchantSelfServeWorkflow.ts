import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantGstinUpdateCustomerActionEnum, DashboardGraphQLMerchantSelfServeGstinPermissionEnum, DashboardGraphQLMerchantGstinWorkflowStatusEnum } from './index';
export type DashboardGraphQLMerchantSelfServeWorkflow = {
  __typename?: 'DashboardGraphQLMerchantSelfServeWorkflow';
  bankAccountId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  customerActions?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantGstinUpdateCustomerActionEnum>>>;
  isRequestUnderBvsValidation: DashboardGraphQLScalars['Boolean'];
  isWorkflowExits: DashboardGraphQLScalars['Boolean'];
  needsClarificationMessage?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  permission?: DashboardGraphQLMaybe<DashboardGraphQLMerchantSelfServeGstinPermissionEnum>;
  rejectedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  rejectionReason?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  workflowStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantGstinWorkflowStatusEnum>;
};