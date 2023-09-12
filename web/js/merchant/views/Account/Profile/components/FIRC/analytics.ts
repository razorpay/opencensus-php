import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

const PURPOSE_CODE_POPUP_EVENT = 'purpose code popup';
const PURPOSE_CODE_POPUP_OPENED = `${PURPOSE_CODE_POPUP_EVENT} opened`;
const PURPOSE_CODE_POPUP_CLOSED = `${PURPOSE_CODE_POPUP_EVENT} closed`;
const PURPOSE_CODE_POPUP_MODE = `${PURPOSE_CODE_POPUP_EVENT} mode`;
const PURPOSE_CODE_POPUP_SAVED = `${PURPOSE_CODE_POPUP_EVENT} save`;
const PURPOSE_CODE_POPUP_SUPPORT_REQUEST = `${PURPOSE_CODE_POPUP_EVENT} support request`;
const PURPOSE_CODE_POPUP_IEC = `${PURPOSE_CODE_POPUP_EVENT} iec code`;
const PURPOSE_CODE_POPUP_SEARCH = `${PURPOSE_CODE_POPUP_EVENT} search`;

const track = ({ properties = {}, ...args }) => {
  analyticsTrack({
    screen: 'Account & Settings',
    ...args,
    properties: {
      ...getCommonSegmentProperties(window.rzp_user),
      ...properties,
    },
  });
};

export const trackPurposeCodePopupOpened = (): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_OPENED,
    actionName: 'clicked',
  });
};

export const trackPurposeCodePopupClosed = (): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_CLOSED,
    actionName: 'clicked',
  });
};

export const trackPurposeCodePopupMode = (isEditMode: boolean, oldPurposeCode?: string): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_MODE,
    actionName: isEditMode ? 'edit' : 'new',
    properties: {
      oldPurposeCode,
    },
  });
};

export const trackPurposeCodeSelected = (newPurposeCode: string): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_EVENT,
    actionName: 'selected',
    properties: {
      newPurposeCode,
    },
  });
};

export const trackPurposeCodeSaved = (purposeCode: string): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_SAVED,
    actionName: 'success',
    properties: {
      purposeCode,
    },
  });
};

export const trackPurposeCodeSavingFailed = (purposeCode: string, errorReason: string): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_SAVED,
    actionName: 'failed',
    properties: {
      purposeCode,
      errorReason,
    },
  });
};

export const trackPurposeCodeUpdateRequestRaised = (
  newPurposeCode: string,
  oldPurposeCode?: string,
): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_SUPPORT_REQUEST,
    actionName: 'success',
    properties: {
      newPurposeCode,
      oldPurposeCode,
    },
  });
};

export const trackPurposeCodeUpdateRequestFailed = (
  newPurposeCode: string,
  oldPurposeCode?: string,
): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_SUPPORT_REQUEST,
    actionName: 'failed',
    properties: {
      newPurposeCode,
      oldPurposeCode,
    },
  });
};

export const trackGoToIECCodeStep = (): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_IEC,
    actionName: 'step',
    properties: {
      specialPurposeCode: true,
    },
  });
};

export const trackPurposeCodeSearched = (): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_SEARCH,
    actionName: 'input',
  });
};

export const trackPurposeCodeSearchCleared = (): void => {
  track({
    objectName: PURPOSE_CODE_POPUP_SEARCH,
    actionName: 'cleared',
  });
};
