import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantGstinUpdate, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantGstinUpdateAsyncFlowSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantGstinUpdateAsyncFlowSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  gstinUpdate: DashboardGraphQLMerchantGstinUpdate;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};