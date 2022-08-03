import analyticsService from '@razorpay/commander-services/analytics';

export const getIsBankingEnabled = (currentUser = {}) => {
  return currentUser.isShowRazorpayXWidgetEnabled && currentUser.isOrgRZP;
};

export const trackSidebarLinkRendered = ({ user = {}, position = '' }) => {
  try {
    analyticsService.track({
      objectName: 'Cash Advance Sidebar Link',
      actionName: 'Rendered',
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
