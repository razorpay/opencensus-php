import { analyticsTrack } from 'common/utils/analytics';
import store from 'merchant/store';

const trackEvent = (obj) => {
  const {
    session: { user },
  } = store.getState();

  try {
    analyticsTrack({
      ...obj,
      properties: {
        ...obj.properties,
        loc_flag: user.isLOCEnabled,
        withdraw_flag: user.isWithdrawFeatureEnabled,
        merchantId: user.current,
      },
    });
  } catch (e) {
    // handle error
  }
};

export const trackLandingonCashAdvanceV2 = () =>
  trackEvent({
    objectName: 'Cash Advance Homepage new',
    actionName: 'Rendered',
    screen: 'Cash Advance || Home Screen',
    properties: {
      tab: 'Cash Advance Homescreen',
      location: 'Begin',
    },
  });

export const trackApplyNow = () =>
  trackEvent({
    objectName: 'Apply Now',
    actionName: 'Clicked',
    screen: 'Cash Advance || Home Screen',
  });

export const trackApplicationStatus = (objectName) =>
  trackEvent({
    objectName,
    actionName: 'Clicked',
    screen: 'Cash Advance || Home Screen',
  });

export const trackLandingOnCashAdvanceV1 = () =>
  trackEvent({
    objectName: 'Cash Advance Homepage current',
    actionName: 'Rendered',
    screen: 'Cash Advance || Home Screen',
    properties: {
      tab: 'Cash Advance Homescreen',
      location: 'Begin',
    },
  });
