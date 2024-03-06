//analytics
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

//event names
const INT_ACTIVATION = 'international activation';
const INT_FORM = `${INT_ACTIVATION} form`;
const INT_REQUEST_BUTTON = `${INT_ACTIVATION} request button`;
const INT_POPUP = `${INT_FORM} popup`;
const INT_POPUP_BUTTON = `${INT_FORM} popup button`;
const INT_SAVE_DATA = `${INT_FORM} save data`;
const INT_PURPOSE_CODE = `${INT_FORM} purpose code`;

//common function
const track = ({ properties, ...args }) => {
  const isIERevamp = window.location.pathname === '/app/payment-methods/international-payments';

  analyticsTrack({
    objectName: INT_ACTIVATION,
    screen: 'settings',
    ...args,
    properties: {
      location: 'Payment Methods',
      timestamp: Date.now(),
      ...(isIERevamp && {
        version: 'v2',
        section: 'International Payments',
        subSection: 'Info Form',
      }),
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
    toLumberjack: true,
  });
};

export const trackIsButtonVisible = (isVisible, buttonText) => {
  track({
    objectName: INT_REQUEST_BUTTON,
    actionName: 'rendered',
    properties: {
      isVisible,
      buttonText,
    },
  });
};

export const trackRequestClicked = (buttonText, formType = 'intl enablement form') => {
  track({
    objectName: INT_REQUEST_BUTTON,
    actionName: 'clicked',
    properties: {
      buttonClicked: true,
      formType,
      buttonText,
    },
  });
};

export const trackModalOpened = () => {
  track({
    objectName: INT_POPUP,
    actionName: 'opened',
    properties: {
      modalOpened: true,
    },
  });
};

export const trackModalClosed = () => {
  track({
    objectName: INT_POPUP,
    actionName: 'closed',
    properties: {
      modalClosed: true,
    },
  });
};

export const trackFormButtonClicked = (tab, buttonText) => {
  track({
    objectName: INT_POPUP_BUTTON,
    actionName: 'clicked',
    properties: {
      isButtonClicked: true,
      buttonText,
      tab,
    },
  });
};

export const trackDataSaving = (isSaving = true, tab, isSubmitted = false) => {
  track({
    objectName: INT_SAVE_DATA,
    actionName: 'initiated',
    properties: {
      isSaving,
      tab,
      isSubmitted,
    },
  });
};

export const trackDataSaveSuccess = (tab, isSubmitted = false) => {
  track({
    objectName: INT_SAVE_DATA,
    actionName: 'response',
    properties: {
      tab,
      status: 'success',
      isSubmitted,
    },
  });
};

export const trackDataSaveError = (tab, errorResponse, isSubmitted = false) => {
  track({
    objectName: INT_SAVE_DATA,
    actionName: 'response',
    properties: {
      tab,
      isSubmitted,
      status: 'error',
      errorResponse,
    },
  });
};

export const trackFormSubmitted = (formType = 'intl enablement form') => {
  track({
    objectName: INT_FORM,
    actionName: 'submitted',
    properties: {
      isSubmitted: true,
      formType,
    },
  });
};

export const trackPurposeCodeChanged = (newPurposeCode, oldPurposeCode) => {
  track({
    objectName: INT_PURPOSE_CODE,
    actionName: 'entered',
    properties: {
      new_purpose_code: newPurposeCode,
      old_purpose_code: oldPurposeCode,
    },
  });
};
