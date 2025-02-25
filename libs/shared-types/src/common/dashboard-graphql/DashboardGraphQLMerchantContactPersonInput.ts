import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantEmailInputField, DashboardGraphQLMerchantStringInputField, DashboardGraphQLMerchantPhoneInputField } from './index';
export type DashboardGraphQLMerchantContactPersonInput = {
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantEmailInputField>;
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantPhoneInputField>;
};