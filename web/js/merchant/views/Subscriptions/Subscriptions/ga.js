import { setTrackData } from 'common/utils/googleAnalytics';

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
