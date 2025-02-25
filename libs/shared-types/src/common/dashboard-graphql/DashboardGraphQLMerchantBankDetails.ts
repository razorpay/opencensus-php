import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantBankDetails = {
  __typename?: 'DashboardGraphQLMerchantBankDetails';
  accountName?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  accountNumber?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  bankAccountId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  bankName?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  ifsc?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};