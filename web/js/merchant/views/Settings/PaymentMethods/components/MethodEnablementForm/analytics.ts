import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

import { ApiDataType } from './types';
import { formatIntlFormDataTrackingObject } from './utils';

const INTL_ADDITIONAL_METHOD_ENABLEMENT = 'international additional methods enablement';
const PREREQUISITE = `${INTL_ADDITIONAL_METHOD_ENABLEMENT} prerequisite info`;
const ADDITIONAL_DOCUMENT = `${INTL_ADDITIONAL_METHOD_ENABLEMENT} additional document`;
const VIDEO_KYC = `${INTL_ADDITIONAL_METHOD_ENABLEMENT} video kyc`;
const VIDEO_KYC_RETRY = `${VIDEO_KYC} retry`;
const UPLOAD_KYC_DOCS = `${INTL_ADDITIONAL_METHOD_ENABLEMENT} upload kyc docs`;
const FORM_DATA = `${INTL_ADDITIONAL_METHOD_ENABLEMENT} form data`;

const actions = {
  CLICKED: 'clicked',
  RESPONSE: 'response',
  SAVED: 'saved',
  SUCCESS: 'success',
  ERROR: 'error',
};

const track = ({ objectName, actionName, properties = {}, ...args }): void => {
  analyticsTrack({
    objectName,
    actionName,
    screen: 'Account & Settings',
    ...args,
    properties: {
      ...getCommonSegmentProperties(window.rzp_user),
      section: 'International Payments',
      subSection: 'Additional method enablement',
      ...properties,
    },
  });
};

export const trackPrerequisiteClicked = (businessType: string | undefined): void => {
  track({
    objectName: PREREQUISITE,
    actionName: actions.CLICKED,
    properties: { business_type: businessType },
  });
};

export const trackDocumentUploadSuccess = ({ businessType, documentType }): void => {
  track({
    objectName: ADDITIONAL_DOCUMENT,
    actionName: actions.SUCCESS,
    properties: {
      business_type: businessType,
      document_type: documentType,
    },
  });
};

export const trackDocumentUploadErr = ({ businessType, documentType, error }): void => {
  track({
    objectName: ADDITIONAL_DOCUMENT,
    actionName: actions.ERROR,
    properties: {
      business_type: businessType,
      document_type: documentType,
      error_code: error?.status_code,
      error_description: error?.errors?.[0],
    },
  });
};

export const trackVideoKycLinkGeneration = (
  businessType: string | undefined,
  authorizedSignatory: boolean,
  errorCode?: string,
  errorDescription?: string,
): void => {
  track({
    objectName: VIDEO_KYC,
    actionName: actions.RESPONSE,
    properties: {
      business_type: businessType,
      authorized_signatory: authorizedSignatory,
      error_code: errorCode,
      error_description: errorDescription,
    },
  });
};

export const trackVideoKycRetryClick = (businessType: string | undefined): void => {
  track({
    objectName: VIDEO_KYC_RETRY,
    actionName: actions.CLICKED,
    properties: { business_type: businessType },
  });
};

export const trackIntlMethodEnablementFormData = (
  businessType: string | undefined,
  data: ApiDataType,
): void => {
  track({
    objectName: FORM_DATA,
    actionName: actions.SAVED,
    properties: { business_type: businessType, ...formatIntlFormDataTrackingObject(data) },
  });
};

export const trackIntlMethodEnablementFormDataErr = ({ error, ...rest }): void => {
  track({
    objectName: FORM_DATA,
    actionName: actions.ERROR,
    properties: {
      ...rest,
      error_code: error?.status_code,
      error_description: error?.errors?.[0],
    },
  });
};

export const trackSubmitForVerificationClicked = (businessType: string | undefined): void => {
  track({
    objectName: UPLOAD_KYC_DOCS,
    actionName: actions.CLICKED,
    properties: { business_type: businessType },
  });
};

export const trackVkycStatusResponse = (
  businessType: string | undefined,
  status: string,
  errorCode?: string,
  errorDescription?: string,
): void => {
  track({
    objectName: VIDEO_KYC,
    actionName: actions.RESPONSE,
    properties: {
      business_type: businessType,
      vcip_status: status,
      error_code: errorCode,
      error_description: errorDescription,
    },
  });
};
