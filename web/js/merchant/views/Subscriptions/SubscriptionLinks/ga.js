import defaultTrack, { setTrackData } from 'common/utils/googleAnalytics';

const eventCategory = 'Dashboard - Subscriptions';

export const track = setTrackData({
  eventCategory,
});

/*
 * Track click on saving duplicate subscription
 * */
export function trackSaveDuplicateSubscription() {
  track({
    eventAction: 'Save - Duplicate Subscription',
  });
}

const eventCategoryInternational = 'Dashboard - International - Subscription';

export function trackAddAddon(currency) {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Select - Addon',
    eventLabel: currency,
  });
}

export function trackAddPlans(currency) {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Select - Plan',
    eventLabel: currency,
  });
}
