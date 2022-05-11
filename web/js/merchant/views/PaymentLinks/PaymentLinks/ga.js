import defaultTrack, { setTrackData } from 'common/utils/googleAnalytics';

const eventCategory = 'Dashboard - Payment Links';

export const track = setTrackData({
  eventCategory,
});

/*
 * Track Edit, Saves, Add, etc clicks in details view.
 * @param {String} action, {String} paymentLinkId
 */
export function trackDetailViewEdits(paymentLinkId, action) {
  track({
    eventAction: `PL Details View - ${action}`,
    eventLabel: 'Payment Links V2',
  });
}

/*
 * Track toggle of partial payment in details view.
 * @param {String} action, {Integer} value, {String} paymentLinkId
 */
export function trackTogglePartialPayment(paymentLinkId, action, value) {
  track({
    eventAction: `PL Details View - ${action}`,
    eventLabel: 'Payment Links V2',
    origin: 'dashboard',
    eventValue: value,
  });
}

/*
 * Track click on Create Payment Link (for V2 users)
 * */
export function trackOpenCreateForm() {
  track({
    eventAction: 'Open Form - New Payment Link',
    eventLabel: 'Payment Links V2',
  });
}

/*
 * Track click on "What's this" helper text
 * */
export function trackHelpClick() {
  track({
    eventAction: "Click - Create Payment Link - What's This",
    eventLabel: 'From PLV2 Create Modal',
  });
}

/*
 * Track click on submit form
 * @params {String} data
 * */
export function trackFormSubmit(data) {
  track({
    eventAction: 'Submit Form - New Payment Link',
    eventLabel: data,
  });
}

/*
 * Track click on submit form
 * @params {String} data
 * */
export function closePaymentLinkForm(text) {
  track({
    eventAction: 'Close Form - New Payment Link',
    eventLabel: `${text} | Payment Links V2`,
  });
}

/*
 * Track click on duplicate payment link button
 * */
export function trackClickDuplicatePaymentLink() {
  track({
    eventAction: 'Click - Duplicate Payment Link',
  });
}

/*
 * Track click on saving duplicate payment link
 * */
export function trackSaveDuplicatePaymentLink() {
  track({
    eventAction: 'Save - Duplicate Payment Link',
  });
}

const eventCategoryInternational = 'Dashboard - International - Payment Links';

export function trackSearchFilterForInternational(filterValue) {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Select - Search filter',
    label: filterValue,
  });
}

export function trackSelectCurrency(currency) {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Select - Currency',
    label: currency,
  });
}
