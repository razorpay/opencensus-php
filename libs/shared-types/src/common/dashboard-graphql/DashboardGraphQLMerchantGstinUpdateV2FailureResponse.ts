import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantGstinUpdateV2FailureResponse = {
  __typename?: 'DashboardGraphQLMerchantGstinUpdateV2FailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};