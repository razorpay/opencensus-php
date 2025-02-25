import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantStringInputField } from './index';
export type DashboardGraphQLMerchantBankInput = {
  accountName?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  accountNumber?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  bankProof?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  ifsc?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
};