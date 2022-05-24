import store from 'merchant/store';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import analyticsService from '@razorpay/commander-services/analytics';

export const EVENT_TYPES = {
  RENDERED: 'Rendered',
  CLICKED: 'clicked',
  HOVERED: 'hovered',
};

/**
 * @param {{objectName, actionName, screen, properties}} obj - Object to be sent to analytics service
 */
const trackEvent = (obj) => {
  const {
    session: { user },
  } = store.getState();

  try {
    analyticsService.track({
      ...obj,
      properties: {
        ...getCommonSegmentProperties(),
        ...obj.properties,
        es_on_demand: user.isOndemandSettlementEnabled,
        es_on_demand_restricted: user.isOndemandSettlementsRestricted,
        es_automatic: user.isAutomaticSettlementEnabled,
        es_automatic_restricted: user.isAutomaticSettlementRestricted,
      },
    });
  } catch (e) {
    // empty catch block
  }
};

export const trackCrossSellBannerRendered = ({ screen }) => {
  trackEvent({
    objectName: 'Cross-Sell Banner',
    actionName: EVENT_TYPES.RENDERED,
    screen,
  });
};

export const trackKnowMoreClicked = ({ screen }) => {
  trackEvent({
    objectName: "'Know More' CTA on cross-sell banner",
    actionName: EVENT_TYPES.CLICKED,
    screen,
  });
};

export const trackEnableNowClicked = ({ screen }) => {
  trackEvent({
    objectName: "'Enable Now' CTA",
    actionName: EVENT_TYPES.CLICKED,
    screen,
  });
};

export const trackEnableModalRendered = ({ screen }) => {
  trackEvent({
    objectName: "'Enable Same-day Settlements' Modal",
    actionName: EVENT_TYPES.RENDERED,
    screen,
  });
};

export const trackEnableModalCloseClick = ({ screen }) => {
  trackEvent({
    objectName: 'Close icon',
    actionName: EVENT_TYPES.CLICKED,
    screen,
  });
};

export const trackExploreNowClicked = () => {
  trackEvent({
    objectName: "'Explore Now' CTA",
    actionName: EVENT_TYPES.CLICKED,
    screen: 'Settlements Page',
  });
};

export const trackSettlementsPageRendered = () => {
  trackEvent({
    objectName: 'Settlements Page',
    actionName: EVENT_TYPES.RENDERED,
    screen: 'Settlements Page',
  });
};

export const trackEnableSamedayBannerRendered = () => {
  trackEvent({
    objectName: 'Enable Sameday Settlements Banner',
    actionName: EVENT_TYPES.RENDERED,
    screen: 'Settlements Page',
  });
};
