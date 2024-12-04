export const getIsBankingEnabled = (currentUser = {}) => {
  return currentUser.isShowRazorpayXWidgetEnabled && currentUser.isOrgRZP;
};

export const getIsPayrollWidgetEnabled = (currentUser = {}) => {
  return currentUser.isShowPayrollWidgetEnabled && currentUser.isOrgRZP;
};

export const getIsShowAffordabilityWidget = (currentUser = {}) => {
  return (
    currentUser.isShowAffordabilityWidget && currentUser.isOrgRZP && currentUser.isCountryIndia
  );
};

export const getIsCheckoutPaymentMetricsEnabled = (currentUser = {}) =>
  currentUser.isCheckoutAnalyticsEnabled && currentUser.isOrgRZP && currentUser.isCountryIndia;

export const isJKOfflineMerchant = (org = {}, user = {}) => {
  return org.isjkOrg && user.isFeatureEnabled('omni_enabled');
};
