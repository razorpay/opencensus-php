import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantGstinUpdateV2, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantGstinUpdateV2SuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantGstinUpdateV2SuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  gstinUpdate: DashboardGraphQLMerchantGstinUpdateV2;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};