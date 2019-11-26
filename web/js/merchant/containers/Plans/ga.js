import defaultTrack, { setTrackData } from 'common/utils/googleAnalytics';

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

/*
 * Track currency selection in create new plan
 * */
export function trackSelectCurrency(currency) {
  defaultTrack({
    eventCategory: 'Dashboard - International - Plan',
    eventAction: 'Select Currency',
    label: currency,
  });
}
