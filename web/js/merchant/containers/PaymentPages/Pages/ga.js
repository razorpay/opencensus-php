import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Payment Pages';

export const track = setTrackData({
  eventCategory,
});

/**
 * Track Edit, Saves, Add, etc clicks in details view.
 * @param {String} action, {String} paymentLinkId
 */
export function trackDetailViewEdits(action, eventLabel) {
  track({
    eventAction: `Details View - ${action}`,
    eventLabel,
  });
}

/**
 * Tracks activities in share modal
 * @param {String} action
 * @param {String} eventLabel
 */
export function trackShareActions(action, eventLabel) {
  track({
    eventAction: `Share - ${action}`,
    eventLabel,
  });
}

/*
* Track click on Create Payment Link (for V2 users)
* */
export function trackCreateActions(action, eventLabel) {
  track({
    eventAction: `Create - Payment Page (${action})`,
    eventLabel,
  });
}

/**
 * Track activities on share modal after successful creation of payment page
 */
export function trackSuccessActions(action, eventLabel) {
  track({
    eventAction: `Success - ${action}`,
    eventLabel,
  });
}

export function trackListActions(action, eventLabel) {
  track({
    eventAction: `List View - ${action}`,
    eventLabel,
  });
}
