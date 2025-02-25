import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantGstinUpdate, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantGstinUpdateInSyncFlowResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantGstinUpdateInSyncFlowResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  gstinUpdate: DashboardGraphQLMerchantGstinUpdate;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};