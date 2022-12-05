export const getIsBankingEnabled = (currentUser = {}) => {
  return currentUser.isShowRazorpayXWidgetEnabled && currentUser.isOrgRZP;
};

export const getIsPayrollWidgetEnabled = (currentUser = {}) => {
  return currentUser.isShowPayrollWidgetEnabled && currentUser.isOrgRZP;
};
