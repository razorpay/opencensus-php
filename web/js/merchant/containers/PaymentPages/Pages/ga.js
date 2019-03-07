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

/*
* Track the selection of template
* */
export function trackTemplateSelection(eventLabel) {
  track({
    eventAction: `Select Template`,
    eventLabel,
  });
}

/*
* Track the selection of template
* */
export function trackGoBackDashboard() {
  track({
    eventAction: 'Create - Click Back to Dashboard',
  });
}

/*
* Track the selection of template
* */
export function trackGoBackToTemplates() {
  track({
    eventAction: 'Create - Click Back to Templates',
  });
}

/*
* Track the start creation of Payment page with what template
* */
export function trackStartCreation(eventLabel) {
  track({
    eventAction: 'Create - Click Lets go',
    eventLabel,
  });
}

/*
* Track the button size selection in 'create button modal'
* */
export function trackCreateButtonSizeSelection(eventLabel) {
  track({
    eventAction: 'Create Payment Button - Copy Code',
    eventLabel,
  });
}

/*
* Track the 'create button modal' close
* */
export function trackCreateButtonCancel() {
  track({
    eventAction: 'Create Payment Button - Cancel',
  });
}

/*
* Track the page settings data
* */
export function trackPageSettingsData(trackData) {
  track({
    eventAction: 'Page Settings',
    eventLabel: trackData.join(' | '),
  });
}
