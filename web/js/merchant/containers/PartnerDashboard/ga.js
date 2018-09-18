import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Partner Dashboard - ';

const trackAffiliateAccounts = setTrackData({
  eventCategory: eventCategory + 'Affiliate Accounts',
});

const trackSettings = setTrackData({
  eventCategory: eventCategory + 'Settings',
});

export function trackListEvents(action) {
  trackAffiliateAccounts({
    eventAction: action + ' - Affiliate Accounts',
  });
}

export function trackSearchAnalytics() {
  trackListEvents('Search');
}

export function trackClearAnalytics() {
  trackListEvents('Clear Search Params');
}

export function trackAddNewMerchantEvents(action) {
  trackAffiliateAccounts({
    eventAction: action + ' - Add New Merchant',
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
