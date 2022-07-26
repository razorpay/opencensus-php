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

export const trackFormButtonClicked = (tab, buttonText) => {
  track({
    objectName: apmPopupButton,
    actionName: 'action',
    properties: {
      isButtonClicked: true,
      buttonText,
      tab,
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

export const trackDataSaving = (isSaving = true, tab, isSubmitted) => {
  track({
    objectName: apmSaveData,
    actionName: 'click',
    properties: {
      isSaving,
      tab,
      isSubmitted,
    },
  });
};

export const trackDataSaveSuccess = (tab, owners = [], isSubmitted) => {
  track({
    objectName: apmSaveData,
    actionName: 'response',
    properties: {
      tab,
      isSubmitted,
      owners: owners?.length,
      status: 'success',
    },
  });
};

export const trackDataSaveError = (tab, isSubmitted, errorResponse) => {
  track({
    objectName: apmSaveData,
    actionName: 'response',
    properties: {
      tab,
      isSubmitted,
      status: 'error',
      errorResponse,
    },
  });
};

export const trackInstrumentsRequested = (instruments = []) => {
  track({
    objectName: apmInstruments,
    actionName: 'requested',
    properties: {
      isSubmitted: true,
      instrumentCount: instruments?.length,
      instruments,
    },
  });
};
