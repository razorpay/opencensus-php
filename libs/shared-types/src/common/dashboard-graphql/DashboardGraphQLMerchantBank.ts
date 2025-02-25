import { DashboardGraphQLMerchantStringField, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantBankVerificationErrorCodeEnum, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantBank = {
  __typename?: 'DashboardGraphQLMerchantBank';
  accountName: DashboardGraphQLMerchantStringField;
  accountNumber: DashboardGraphQLMerchantStringField;
  /** fuzzyScore is used to match the DashboardGraphQLBank Account details with the given PAN Details */
  fuzzyScore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  ifsc: DashboardGraphQLMerchantStringField;
  verificationErrorCode?: DashboardGraphQLMaybe<DashboardGraphQLMerchantBankVerificationErrorCodeEnum>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};