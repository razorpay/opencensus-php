import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Plans';

export const track = setTrackData({
  eventCategory,
});

/*
* Track click on duplicate plan button
* */
export function trackClickDuplicatePlan() {
  track({
    eventAction: 'Click - Duplicate Plan',
  });
}

/*
* Track click on saving duplicate plan
* */
export function trackSaveDuplicatePlan() {
  track({
    eventAction: 'Save - Duplicate Plan',
  });
}
