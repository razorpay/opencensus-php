//analytics
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

//constants
import { apmOnboarding, apmPopup, apmPopupButton, apmSaveData, apmInstruments } from './constants';

//common function
const track = ({ properties, ...args }) => {
  analyticsTrack({
    objectName: apmOnboarding,
    screen: 'settings',
    ...args,
    properties: {
      location: 'Payment Methods',
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
  });
};

export const trackModalOpened = (isEditDraft) => {
  track({
    objectName: apmPopup,
    actionName: 'rendered',
    properties: {
      modalOpened: true,
      isEditDraft,
    },
  });
};

export const trackModalClosed = (isSubmitted = false) => {
  track({
    objectName: apmPopup,
    actionName: 'rendered',
    properties: {
      modalClosed: true,
      isSubmitted,
    },
  });
};

export const trackFormButtonClicked = (buttonText) => {
  track({
    objectName: apmPopupButton,
    actionName: 'action',
    properties: {
      isButtonClicked: true,
      buttonText,
    },
  });
};

export const trackFormAddOwnerClicked = () => {
  track({
    objectName: apmPopupButton,
    actionName: 'action',
    properties: {
      isButtonClicked: true,
      buttonText: 'Add Owner',
    },
  });
};

export const trackFormOwnerDeleted = (ownerId) => {
  track({
    objectName: apmPopupButton,
    actionName: 'action',
    properties: {
      isButtonClicked: true,
      buttonIcon: 'bin',
      ownerId,
    },
  });
};

export const trackDataSaving = (isSaving = true, isSubmitted) => {
  track({
    objectName: apmSaveData,
    actionName: 'click',
    properties: {
      isSaving,
      isSubmitted,
    },
  });
};

export const trackDataSaveSuccess = (isSubmitted) => {
  track({
    objectName: apmSaveData,
    actionName: 'response',
    properties: {
      isSubmitted,
      status: 'success',
    },
  });
};

export const trackDataSaveError = (isSubmitted, errorResponse) => {
  track({
    objectName: apmSaveData,
    actionName: 'response',
    properties: {
      isSubmitted,
      status: 'error',
      errorResponse,
    },
  });
};

export const trackInstrumentsRequested = (instruments) => {
  track({
    objectName: apmInstruments,
    actionName: 'requested',
    properties: {
      isSubmitted: true,
      instruments,
    },
  });
};
