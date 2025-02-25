import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantBusinessAddressInput, DashboardGraphQLMerchantStringInputField, DashboardGraphQLScalars, DashboardGraphQLMerchantPaymentAcceptanceChannelsInput, DashboardGraphQLMerchantShopEstablishmentInput, DashboardGraphQLMerchantBusinessTypeInputField, DashboardGraphQLMerchantUrlInputField } from './index';
export type DashboardGraphQLMerchantBusinessInput = {
  address?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBusinessAddressInput>;
  billingLabel?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  blacklistedConsentCategory?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  businessPan?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  category?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  companyCin?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  gstin?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  model?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  parentCategory?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  paymentAcceptanceChannels?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantPaymentAcceptanceChannelsInput>;
  shopEstablishment?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantShopEstablishmentInput>;
  subCategory?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  type?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBusinessTypeInputField>;
  websites?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLInputMaybe<DashboardGraphQLMerchantUrlInputField>>>;
};