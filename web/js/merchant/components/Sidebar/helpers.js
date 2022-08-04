import analyticsService from '@razorpay/commander-services/analytics';

export const getIsBankingEnabled = (currentUser = {}) => {
  return currentUser.isShowRazorpayXWidgetEnabled && currentUser.isOrgRZP;
};

const trackCashAdvance = ({ user, position, actionName }) => {
  try {
    analyticsService.track({
      objectName: 'Cash Advance Sidebar Link',
      actionName,
      screen: location.pathname,
      properties: {
        position,
        loc_flag: user.isLOCEnabled,
        withdraw_flag: user.isWithdrawFeatureEnabled,
      },
    });
  } catch (e) {
    // handle error
  }
};

export const trackCashAdvanceSidebarLinkRendered = ({ user = {}, position = '' }) => {
  trackCashAdvance({
    user,
    position,
    actionName: 'Rendered',
  });
};

export const trackCashAdvanceSidebarLinkClicked = ({ user = {}, position = '' }) => {
  trackCashAdvance({
    user,
    position,
    actionName: 'Clicked',
  });
};
