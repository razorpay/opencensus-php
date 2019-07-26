import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Subscriptions';

export const track = setTrackData({
  eventCategory,
});

/*
* Track click on duplicate subscription
* */
export function trackClickDuplicateSubscription() {
  track({
    eventAction: 'Click - Duplicate Subscription',
  });
}

/*
* Track click on saving duplicate subscription
* */
export function trackSaveDuplicateSubscription() {
  track({
    eventAction: 'Save - Duplicate Subscription',
  });
}
