import { setTrackData } from 'common/utils/googleAnalytics';
import { titleCase, getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

const eventCategory = 'Dashboard - Payment Pages';

export const track = setTrackData({
  eventCategory,
});

/**
 * Track Edit, Saves, Add, etc clicks in details view.
 * @param {String} action, {String} paymentLinkId
 * @param {String} eventLabel additional info of event,
 */
export function trackDetailViewEdits(action, eventLabel) {
  track({
    eventAction: `Details View - ${action}`,
    eventLabel,
  });
}

/**
 * Tracks activities in share modal
 * @param {String} action ga action like click send
 * @param {String|Object} eventLabel addtional info on event
 */
export function trackShareActions(action, eventLabel) {
  track({
    eventAction: `Share - ${titleCase(action)}`,
    eventLabel: typeof eventLabel === 'object' ? getKeysSeparatedByPipe(eventLabel) : eventLabel,
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
 * @param {String} action ga action like click send
 * @param {String} eventLabel addtional info on event
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
 * label: 'Save and Publish', 'Save'
 * */
export function trackPageSettingsData(label, trackData) {
  track({
    eventAction: `Page Settings - ${label}`,
    eventLabel: trackData.join(' | '),
  });
}

export function trackWYSIWYGCloseIntent() {
  track({
    eventAction: 'Close - WYWISYG (intent)',
  });
}

export function trackConfirmWYSIWYGCloseIntent() {
  track({
    eventAction: 'Close - WYWISYG (confirmed)',
  });
}

export function trackPageSettingsClick() {
  track({
    eventAction: 'Open - Page Settings',
  });
}

/*
 * Track creation / updation of payment pages
 * type: create / save
 * */
export function trackPageSave(type, trackData) {
  if (['create', 'save'].indexOf(type.toLowerCase()) < -1) {
    throw new Error('Invalid track type for saving payment pages');
  }

  track({
    eventAction: `Create - ${titleCase(type)} and Publish`,
    eventLabel: trackData.join(' | '),
  });
}

/*
 * Track the click on create embed button
 * */
export function trackClickOnCreateEmbedButton(eventLabel) {
  track({
    eventAction: 'Click - Create Embed Button',
    eventLabel, // eventLabel would be '' / 'new'
  });
}
