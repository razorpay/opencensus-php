import { DashboardGraphQLMerchantBankDetails, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantBankDetailsSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantBankDetailsSuccessResponse';
  bank: DashboardGraphQLMerchantBankDetails;
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};