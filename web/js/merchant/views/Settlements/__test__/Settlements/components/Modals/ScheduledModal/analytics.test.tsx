import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import {
  EVENT_TYPES,
  trackEnableModalRendered,
  trackCrossSellBannerRendered,
  trackKnowMoreClicked,
  trackEnableNowClicked,
  trackEnableModalCloseClick,
  trackExploreNowClicked,
  trackSettlementsPageRendered,
  trackEnableSamedayBannerRendered,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/analytics';

test('should trigger correct analytics event for trackEnableNowClicked', () => {
  trackEnableNowClicked({
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: "'Enable Now' CTA",
    actionName: EVENT_TYPES.CLICKED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
});

test('should trigger correct analytics event for trackEnableModalCloseClick', () => {
  trackEnableModalCloseClick({
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: 'Close icon',
    actionName: EVENT_TYPES.CLICKED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
});

test('should trigger correct analytics event for trackExploreNowClicked', () => {
  trackExploreNowClicked();
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: "'Explore Now' CTA",
    actionName: EVENT_TYPES.CLICKED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen: 'Settlements Page',
  });
});

test('should trigger correct analytics event for trackSettlementsPageRendered', () => {
  trackSettlementsPageRendered();
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: 'Settlements Page',
    actionName: EVENT_TYPES.RENDERED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen: 'Settlements',
  });
});

test('should trigger correct analytics event for trackEnableSamedayBannerRendered', () => {
  trackEnableSamedayBannerRendered();
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: 'Enable Sameday Settlements Banner',
    actionName: EVENT_TYPES.RENDERED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen: 'Settlements Page',
  });
});

test('should trigger correct analytics event for trackEnableModalRendered', () => {
  trackEnableModalRendered({
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: "'Enable Same-day Settlements' Modal",
    actionName: EVENT_TYPES.RENDERED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
});

test('should trigger correct analytics event for trackCrossSellBannerRendered', () => {
  trackCrossSellBannerRendered({
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: 'Cross-Sell Banner',
    actionName: EVENT_TYPES.RENDERED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
});

test('should trigger correct analytics event for trackKnowMoreClicked', () => {
  trackKnowMoreClicked({
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
  expect(analyticsTrack).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledWith({
    objectName: "'Know More' CTA on cross-sell banner",
    actionName: EVENT_TYPES.CLICKED,
    properties: {
      es_on_demand: false,
      es_on_demand_restricted: false,
      es_automatic: false,
      es_automatic_restricted: false,
      ...getCommonSegmentProperties(),
    },
    screen:
      'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal',
  });
});
