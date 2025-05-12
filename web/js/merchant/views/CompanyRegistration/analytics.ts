import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getClientID } from 'common/services/tracking/segment';

const RizeIncorporationScreenOneEvents = {
  LANDING_PAGE_VIEW: {
    objectName: 'Page',
    actionName: 'Viewed',
    screen: 'Rize Company Registration',
  },
  WEBSITE_CTA_CLICK: {
    objectName: 'Website Cta',
    actionName: 'Clicked',
    screen: 'Rize Company Registration',
  },
  PUBLIC_URL_CLICK: {
    objectName: 'Link',
    actionName: 'Clicked',
    screen: 'Rize Company Registration',
  },
  // SCREEN 2 EVENTS
  USER_SCREEN_PAGE_VIEW: {
    objectName: 'Page',
    actionName: 'Viewed',
    screen: 'Rize Resume Company Registration',
  },
  USER_SCREEN_CTA_CLICK: {
    objectName: 'Website Cta',
    actionName: 'Clicked',
    screen: 'Rize Resume Company Registration',
  },
  // SCREEN 3 EVENTS
  USER_STATUS_PAGE_VIEW: {
    objectName: 'Page',
    actionName: 'Viewed',
    screen: 'Rize Application Under Review',
  },
  // SCREEN 4 EVENTS
  INCORPORATION_COMPLETE_PAGE_VIEW: {
    objectName: 'Page',
    actionName: 'Viewed',
    screen: 'Rize Incorporation Completed',
  },
  INCORPORATION_COMPLETE_LINK_CLICK: {
    objectName: 'Link',
    actionName: 'Clicked',
    screen: 'Rize Incorporation Completed',
  },
  INCORPORATION_COMPLETE_CTA_CLICK: {
    objectName: 'Website Cta',
    actionName: 'Clicked',
    screen: 'Rize Incorporation Completed',
  },
  // SCREEN 5 EVENTS
  CREATE_ACCOUNT_PAGE_VIEW: {
    objectName: 'Page',
    actionName: 'Viewed',
    screen: 'Rize Choose Account',
  },
  CREATE_ACCOUNT_FORM_FIELD: {
    objectName: 'Form Field',
    actionName: 'Selected',
    screen: 'Rize Choose Account',
  },
  CREATE_ACCOUNT_CTA_CLICK: {
    objectName: 'Website Cta',
    actionName: 'Clicked',
    screen: 'Rize Choose Account',
  },
};
const commonProperties = {
  label: 'Company Registration',
  section: 'Rize Company Registration',
  subSection: 'Rize Company Registration ',
  l1FunnelStage: 'Rize Company Registration',
};

const track: typeof analyticsTrack = ({ properties, ...rest }) => {
  return analyticsTrack({
    ...rest,
    properties: {
      clientId: getClientID(),
      url: window.location.href,
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
  });
};

export const trackLandingPageView = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.LANDING_PAGE_VIEW,
    properties: {
      ...commonProperties,
      label: 'Register your Business now',
      l2FunnelStage: 'Page View',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
export const trackStartRegistrationCtaClick = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.WEBSITE_CTA_CLICK,
    properties: {
      ...commonProperties,
      l2FunnelStage: 'CTA Clicked',
      label: 'Register your Business now',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};

export const trackEventOnPublicUrl = ({ link_url, label }): void => {
  track({
    ...RizeIncorporationScreenOneEvents.PUBLIC_URL_CLICK,
    properties: {
      ...commonProperties,
      l2FunnelStage: 'Link Clicked',
      label,
      link_url,
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
// SCREEN 2 
export const trackEventOnUserScreenPageView = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.USER_SCREEN_PAGE_VIEW,
    properties: {
      ...commonProperties,
      formName: 'Resume Incorporation',
      section: 'Resume Incorporation',
      subSection: 'Resume Incorporation',
      l2FunnelStage: 'Resume Incorporation',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
export const trackEventOnUserScreenCtaClick = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.USER_SCREEN_CTA_CLICK,
    properties: {
      ...commonProperties,
      label: 'Resume Company Incorporation',
      section: 'Resume Incorporation',
      subSection: 'Resume Incorporation',
      l2FunnelStage: 'Resume Incorporation',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
// SCREEN 3
export const trackEventOnUserStatusPageView = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.USER_STATUS_PAGE_VIEW,
    properties: {
      ...commonProperties,
      formName: 'Incorp Application Under Review',
      section: 'Incorp Application',
      subSection: 'Incorp Application',
      l2FunnelStage: 'Incorp Application',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
// SCREEN 4
export const trackEventOnIncorporationCompletePageView = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.INCORPORATION_COMPLETE_PAGE_VIEW,
    properties: {
      ...commonProperties,
      formName: 'Incorporation Complete',
      section: 'Incorporation Complete',
      subSection: 'Incorporation Complete',
      l2FunnelStage: 'Incorporation Complete',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
export const trackEventOnIncorporationCompleteLinkClick = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.INCORPORATION_COMPLETE_LINK_CLICK,
    properties: {
      ...commonProperties,
      label: 'Create Account',
      section: 'Incorporation Complete',
      subSection: 'Incorporation Complete',
      l2FunnelStage: 'Create Incorporation account',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
export const trackEventOnIncorporationCompleteCtaClick = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.INCORPORATION_COMPLETE_CTA_CLICK,
    properties: {
      ...commonProperties,
      label: 'Download Incorporation Documents',
      section: 'Incorporation Complete',
      subSection: 'Incorporation Complete',
      l2FunnelStage: 'Create Incorporation account ',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
// SCREEN 5
export const trackEventOnCreateAccountPageView = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.CREATE_ACCOUNT_PAGE_VIEW,
    properties: {
      ...commonProperties,
      l2FunnelStage: 'Choose Account',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
export const trackEventOnCreateAccountFormField = (isNewAccount: boolean): void => {
  track({
    ...RizeIncorporationScreenOneEvents.CREATE_ACCOUNT_FORM_FIELD,
    properties: {
      ...commonProperties,
      formName: 'Choose Account',
      fieldName: isNewAccount  ? 'New Account' : 'Existing',
      fieldType: 'Text box',
      l2FunnelStage: 'Choose Account',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
export const trackEventOnCreateAccountCtaClick = (): void => {
  track({
    ...RizeIncorporationScreenOneEvents.CREATE_ACCOUNT_CTA_CLICK,
    properties: {
      ...commonProperties,
      label: 'Proceed',
      section: 'Choose Account',
      subSection: 'Choose Account',
      l2FunnelStage: 'Choose Account',
      utm_source: 'direct',
      utm_medium: 'rize_razorpay_dashboard',
    },
  });
};
