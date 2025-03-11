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
