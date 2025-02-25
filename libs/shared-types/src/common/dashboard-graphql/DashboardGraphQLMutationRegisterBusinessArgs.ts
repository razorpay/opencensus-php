import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantBusinessTypeEnum, DashboardGraphQLPhoneInput, DashboardGraphQLScalars, DashboardGraphQLMerchantMonthlyRevenueEnum } from './index';
export type DashboardGraphQLMutationRegisterBusinessArgs = {
  businessType?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBusinessTypeEnum>;
  contact?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  couponCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  name: DashboardGraphQLScalars['String'];
  partnerId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  referralCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  transactionVolume?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantMonthlyRevenueEnum>;
};