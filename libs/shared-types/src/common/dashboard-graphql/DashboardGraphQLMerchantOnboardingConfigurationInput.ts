import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantConfigurationNamespaceEnum, DashboardGraphQLUpiTerminalProcurementStatusEnum } from './index';
export type DashboardGraphQLMerchantOnboardingConfigurationInput = {
  couponPopupCount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  isMtuCouponCongratulatoryPopupEnabled?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  namespace: DashboardGraphQLMerchantConfigurationNamespaceEnum;
  referralSuccessPopupCount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  showFtuxFinalScreen?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  showFtuxFirstPaymentBanner?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  upiTerminalProcurementStatus?: DashboardGraphQLInputMaybe<DashboardGraphQLUpiTerminalProcurementStatusEnum>;
  websiteIncompleteSoftNudgeCount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Int']>;
  websiteIncompleteSoftNudgeTimestamp?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Int']>;
};