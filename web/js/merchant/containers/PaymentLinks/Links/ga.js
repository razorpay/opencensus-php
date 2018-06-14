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
    eventLabel: `payment_link_id=${paymentLinkId}`,
  });
}

/**
 * Track toggle of partial payment in details view.
 * @param {String} action, {Integer} value, {String} paymentLinkId
 */
export function trackTogglePartialPayment(paymentLinkId, action, value) {
  track({
    eventAction: `PL Details View - ${action}`,
    eventLabel: `payment_link_id=${paymentLinkId}`,
    eventValue: value,
  });
}

/*
* Track click on Create Payment Link (for V2 users)
* */
export function trackOpenCreateForm(e) {
  track({
    eventAction: 'Open Form - New Payment Link V2',
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
