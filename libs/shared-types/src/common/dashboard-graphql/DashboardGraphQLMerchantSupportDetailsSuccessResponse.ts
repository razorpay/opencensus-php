import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantSupportDetails } from './index';
export type DashboardGraphQLMerchantSupportDetailsSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantSupportDetailsSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  details?: DashboardGraphQLMaybe<DashboardGraphQLMerchantSupportDetails>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};