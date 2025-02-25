import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantContact, DashboardGraphQLMerchantContactFundAccountDetails, DashboardGraphQLMerchantContactFundAccountTypeEnum } from './index';
export type DashboardGraphQLMerchantContactFundAccount = {
  __typename?: 'DashboardGraphQLMerchantContactFundAccount';
  active: DashboardGraphQLScalars['Boolean'];
  batchId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  contact: DashboardGraphQLMerchantContact;
  createdAt: DashboardGraphQLScalars['DateTime'];
  details?: DashboardGraphQLMaybe<DashboardGraphQLMerchantContactFundAccountDetails>;
  id: DashboardGraphQLScalars['ID'];
  type: DashboardGraphQLMerchantContactFundAccountTypeEnum;
};