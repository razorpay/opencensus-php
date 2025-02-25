import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantGstinUpdate, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantGstinUpdateInSyncWorkFlowCreatedResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantGstinUpdateInSyncWorkFlowCreatedResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  gstinUpdate: DashboardGraphQLMerchantGstinUpdate;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};