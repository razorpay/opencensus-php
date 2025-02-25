import { DashboardGraphQLMerchantBusinessAddress, DashboardGraphQLMerchantAverageOrderField, DashboardGraphQLMerchantStringField, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantGstinVerificationErrorCodeEnum, DashboardGraphQLMerchantPaymentAcceptanceChannels, DashboardGraphQLMerchantShopEstablishment, DashboardGraphQLMerchantBusinessTypeField, DashboardGraphQLMerchantUrlField } from './index';
export type DashboardGraphQLMerchantBusiness = {
  __typename?: 'DashboardGraphQLMerchantBusiness';
  address: DashboardGraphQLMerchantBusinessAddress;
  averageOrder: DashboardGraphQLMerchantAverageOrderField;
  billingLabel: DashboardGraphQLMerchantStringField;
  blacklistedConsentCategory?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  businessPan: DashboardGraphQLMerchantStringField;
  category: DashboardGraphQLMerchantStringField;
  companyCin: DashboardGraphQLMerchantStringField;
  gstin: DashboardGraphQLMerchantStringField;
  gstinVerificationErrorCode?: DashboardGraphQLMaybe<DashboardGraphQLMerchantGstinVerificationErrorCodeEnum>;
  model: DashboardGraphQLMerchantStringField;
  name: DashboardGraphQLMerchantStringField;
  parentCategory: DashboardGraphQLMerchantStringField;
  paymentAcceptanceChannels: DashboardGraphQLMerchantPaymentAcceptanceChannels;
  shopEstablishment: DashboardGraphQLMerchantShopEstablishment;
  subCategory: DashboardGraphQLMerchantStringField;
  type: DashboardGraphQLMerchantBusinessTypeField;
  /** @deprecated Use paymentAcceptanceChannels.websites.urls[0].value */
  websites: Array<DashboardGraphQLMerchantUrlField>;
};