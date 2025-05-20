import { analyticsTrack, getCommonSegmentProperties } from '@libs/shared-utils';

const EVENT_ACTIONS = {
  VIEW: 'viewed',
  CLICK: 'clicked',
  SELECT: 'selected',
};

interface TrackEventsProps {
  eventName: string;
  action: string;
  properties?: any;
}

const trackEvent = ({ eventName, action, properties = {} }: TrackEventsProps): void => {
  analyticsTrack({
    objectName: eventName,
    actionName: action,
    properties: {
      ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
      ...properties,
    },
    screen: window.location.pathname.split('/').pop() || '',
    toLumberjack: true,
  });
};

// Landing Page events
export const trackBpLandingPageView = () => {
  trackEvent({
    eventName: 'BP landing page',
    action: EVENT_ACTIONS.VIEW,
  });
};

export const trackBpLearnMoreClick = () => {
  trackEvent({
    eventName: 'BP Learn more',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpSetupNowClick = () => {
  trackEvent({
    eventName: 'BP Setup now',
    action: EVENT_ACTIONS.CLICK,
  });
};

// Info & Activation events
export const trackBpInfoPageView = () => {
  trackEvent({
    eventName: 'BP info page',
    action: EVENT_ACTIONS.VIEW,
  });
};

export const trackBpKnowMoreClick = () => {
  trackEvent({
    eventName: 'BP Know more',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpActivateNowClick = () => {
  trackEvent({
    eventName: 'BP Activate now',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpTermsConditionsLinkClick = () => {
  trackEvent({
    eventName: 'BP Terms and Conditions link',
    action: EVENT_ACTIONS.CLICK,
  });
};

// Terms & Conditions Modal events
export const trackBpTermsModalView = () => {
  trackEvent({
    eventName: 'BP terms modal',
    action: EVENT_ACTIONS.VIEW,
  });
};

export const trackBpAgreeActivateClick = () => {
  trackEvent({
    eventName: 'BP Agree and activate',
    action: EVENT_ACTIONS.CLICK,
  });
};

// Setup Platform & Integration events
export const trackBpSetupPlatformPageView = () => {
  trackEvent({
    eventName: 'BP setup platform page',
    action: EVENT_ACTIONS.VIEW,
  });
};

export const trackBpPlatformSelectShopifyClick = () => {
  trackEvent({
    eventName: 'BP Shopify platform',
    action: EVENT_ACTIONS.SELECT,
  });
};

export const trackBpPlatformSelectOtherClick = () => {
  trackEvent({
    eventName: 'BP Other platform',
    action: EVENT_ACTIONS.SELECT,
  });
};

export const trackBpViewIntegrationStepsClick = () => {
  trackEvent({
    eventName: 'BP View integration steps',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpStartSetupClick = () => {
  trackEvent({
    eventName: 'BP Start setup',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpVideoClick = () => {
  trackEvent({
    eventName: 'BP Shopify video',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpVideoModalView = () => {
  trackEvent({
    eventName: 'BP Shopify video modal',
    action: EVENT_ACTIONS.VIEW,
  });
};

export const trackBpMailClick = () => {
  trackEvent({
    eventName: 'BP Write a mail',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpMenuOpen = (isMenuOpen: boolean) => {
  trackEvent({
    eventName: 'BP Menu',
    action: EVENT_ACTIONS.CLICK,
    properties: {
      isMenuOpen,
    },
  });
};

export const trackBpDeactivateBuyerProtectionClick = () => {
  trackEvent({
    eventName: 'BP Deactivate buyer protection',
    action: EVENT_ACTIONS.CLICK,
  });
};

export const trackBpContactSupportClick = () => {
  trackEvent({
    eventName: 'BP Contact support',
    action: EVENT_ACTIONS.CLICK,
  });
};
