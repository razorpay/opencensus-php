import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Payment Links';

export const track = setTrackData({
  eventCategory,
});

/**
 * Track Edit, Saves, Add, etc clicks in details view.
 * @param {String} action, {String} paymentLinkId
 */
export function trackDetailViewEdits(paymentLinkId, action) {
  track({
    eventAction: `PL Details View - ${action}`,
    eventLabel: 'Payment Links V2',
  });
}

/**
 * Track toggle of partial payment in details view.
 * @param {String} action, {Integer} value, {String} paymentLinkId
 */
export function trackTogglePartialPayment(paymentLinkId, action, value) {
  track({
    eventAction: `PL Details View - ${action}`,
    eventLabel: 'Payment Links V2',
    eventValue: value,
  });
}

/*
* Track click on Create Payment Link (for V2 users)
* */
export function trackOpenCreateForm(e) {
  track({
    eventAction: 'Open Form - New Payment Link',
    eventLabel: 'Payment Links V2',
  });
}

/*
* Track click on "What's this" helper text
* */
export function trackHelpClick(e) {
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
    eventLabel: text + ' | Payment Links V2',
  });
}
