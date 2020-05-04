import { setTrackData } from 'common/utils/googleAnalytics';
import { mainFormTabs } from 'merchant/components/Activation/ActivationFormMap';

const eventCategory = 'Dashboard - Activation Form v2';

export let track = setTrackData({
  eventCategory,
});

export const setInstantActivationsTracking = () => {
  track = setTrackData({
    eventCategory: 'Dashboard - Instant Activations KYC',
  });
};

/* Utility to create eventAction based on  api response and event. */
function _pipeActionWithType(action, result) {
  let eventAction = action;
  if (typeof result !== 'undefined') {
    eventAction += ` (${result ? 'Success' : 'Error'})`;
  } else {
    eventAction += ' (No Change)';
  }

  return eventAction;
}

/* Utility to create eventLabel based on  api response and event. */
function _pipeLabelWithError(label, result, error) {
  if (result === false) {
    // Error case
    label = label + '| ' + JSON.stringify(error);
  }

  return label;
}

/* Track tab change clicks on activation form */
export const trackTabClick = tabId => {
  track({
    eventAction: 'Click - Activation Tab',
    eventLabel: mainFormTabs[tabId],
  });
};

/* Track 'submit form' click in activation form */
export const trackSubmitFormTabClick = () => {
  track({
    eventAction: 'Click - Activation Tab',
    eventLabel: 'Submit Form',
  });
};

/* Track 'submit KYC' click from dashboard/my account in activation form */
export const trackKYCFormOpen = () => {
  track({
    eventAction: 'Click - Open',
  });
};

/* Track tab saved which triggered on tab change clicks */
export const trackSaveOnTabClick = data => {
  track({
    eventAction: _pipeActionWithType(
      'Click - Activation Tab || Saved',
      data.type
    ), // type = Success / Error
    eventLabel: _pipeLabelWithError(
      mainFormTabs[data.tabId],
      data.type,
      data.error
    ),
  });
};

/* Track 'Save and Next' btn in footer of activation form */
export const trackSaveAndNext = data => {
  track({
    eventAction: _pipeActionWithType('Click - Save and Next', data.type), // type = Success / Error
    eventLabel: _pipeLabelWithError(
      mainFormTabs[data.tabId],
      data.type,
      data.error
    ),
  });
};

/* Track 'Save' btn in footer of activation form */
export const trackSave = data => {
  track({
    eventAction: _pipeActionWithType('Click - Save', data.type), // type = Success / Error
    eventLabel: _pipeLabelWithError(
      mainFormTabs[data.tabId],
      data.type,
      data.error
    ),
  });
};

/* Track 'Submit' btn in footer of activation form */
export const trackSubmit = data => {
  let eventLabel = '';

  if (data.error) {
    eventLabel = _pipeLabelWithError(
      mainFormTabs[data.tabId],
      data.type,
      data.error
    );
  } else {
    const _activationFlow = data.isUnregisteredBusiness
      ? 'unregistered'
      : data.activationFlow;
    eventLabel = `Instant Activation | ${_activationFlow}`;
  }

  track({
    eventAction: _pipeActionWithType('Click - Submit', data.type), // type = Success / Error
    eventLabel,
  });
};

/* Track 'Back' btn */
export const trackBack = data => {
  track({
    eventAction: _pipeActionWithType('Click - Back', data.type, data.error), // type = Success / Error
    eventLabel: data.tab_id,
  });
};

/* Tracks following:
 * - link clicks : 'Terms & Conditions', 'Merchant Agreement' and 'Privacy Policy'
 * - 'Activate Now' btn on Welcome screen
 **/
export const trackLinkClick = action => {
  track({
    eventAction: 'Click - ' + action,
  });
};

/* Click 'Activation Form' */
export const trackGoToConfig = () => {
  track({
    eventAction: 'Go to - Config',
    eventLabel: 'From Activation Success Modal',
  });
};

/* Instant Activations Tracking, L1 form is instant activation form */

const trackIA = setTrackData({
  eventCategory: 'Dashboard - Instant Activations Activate Account',
});

export const trackL1FormSuccess = user => {
  const eventLabel = user.isUnregisteredBusiness
    ? 'unregistered'
    : user.activation_flow; // activation_flow = one of [whitelist,greylist,blacklist]

  trackIA({
    eventAction: 'Click - Activate Account (Success)',
    eventLabel,
  });
};

export const trackL1FormError = () => {
  trackIA({
    eventAction: 'Click - Activate Account (Error)',
  });
};

export const trackTnCClick = () => {
  trackIA({
    eventAction: 'Click - Terms and Conditions',
  });
};

export default track;
