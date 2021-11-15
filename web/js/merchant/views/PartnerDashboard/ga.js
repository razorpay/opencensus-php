import { setTrackData } from 'common/utils/googleAnalytics';
import store from 'merchant/store';

const eventCategory = 'Partner Dashboard - ';

const trackAffiliateAccounts = setTrackData({
  eventCategory: `${eventCategory}Affiliate Accounts`,
});

const trackSettings = setTrackData({
  eventCategory: `${eventCategory}Settings`,
});

export function trackReferral() {
  trackAffiliateAccounts({
    eventAction: 'Share Referral Link - Affiliate Accounts',
    eventLabel: 'Click on Copy',
  });
}

export function trackListEvents(action) {
  trackAffiliateAccounts({
    eventAction: `${action} - Affiliate Accounts`,
  });
}

export function trackSearchAnalytics() {
  trackListEvents('Search');
}

export function trackClearAnalytics() {
  trackListEvents('Clear Search Params');
}

export function trackAddNewMerchantEvents(action) {
  const user = store.getState().session.user;
  const businessTypeName = user.isUnregisteredBusiness ? 'Unregistered' : 'Registered';

  trackAffiliateAccounts({
    eventAction: `${action} - Add New Merchant`,
    eventLabel: `Add Sub Merchant - ${businessTypeName}`,
  });
}

export function trackSwitchMerchantClick() {
  trackAffiliateAccounts('Click - Switch In Row');
}

export function trackSettingsEvents() {
  trackSettings({
    eventAction: 'Go To - Settings',
  });
}
