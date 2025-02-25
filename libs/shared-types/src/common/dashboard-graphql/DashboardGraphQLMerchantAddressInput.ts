import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantStringInputField } from './index';
export type DashboardGraphQLMerchantAddressInput = {
  city?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  country?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  district?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  line1?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  line2?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  state?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  zipCode?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
};