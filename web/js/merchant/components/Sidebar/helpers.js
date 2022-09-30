export const getIsBankingEnabled = (currentUser = {}) => {
  return currentUser.isShowRazorpayXWidgetEnabled && currentUser.isOrgRZP;
};
