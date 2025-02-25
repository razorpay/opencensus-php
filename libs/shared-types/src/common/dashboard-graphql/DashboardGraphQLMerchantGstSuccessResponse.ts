import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantGstSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantGstSuccessResponse';
  gst?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};