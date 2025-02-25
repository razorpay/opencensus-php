import { DashboardGraphQLMaybe, DashboardGraphQLMerchantBankingAccountBalance, DashboardGraphQLCurrency, DashboardGraphQLScalars, DashboardGraphQLMerchantBankingAccountStatusEnum, DashboardGraphQLMerchantBankingAccountTypeEnum } from './index';
export type DashboardGraphQLMerchantBankingAccount = {
  __typename?: 'DashboardGraphQLMerchantBankingAccount';
  balance?: DashboardGraphQLMaybe<DashboardGraphQLMerchantBankingAccountBalance>;
  currency: DashboardGraphQLCurrency;
  id: DashboardGraphQLScalars['ID'];
  ifsc?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  number?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  partnerBankName: DashboardGraphQLScalars['String'];
  pincode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['PositiveInt']>;
  status: DashboardGraphQLMerchantBankingAccountStatusEnum;
  type: DashboardGraphQLMerchantBankingAccountTypeEnum;
};