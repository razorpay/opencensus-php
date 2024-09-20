import { analyticsTrack } from 'common/utils/analytics';
import { camelize, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { flashCheckoutProps } from 'merchant/views/Settings/Configuration/settings-config-constants';

const analyticsObjName = flashCheckoutProps.featureName.toLowerCase();
const analyticsLabel = camelize(flashCheckoutProps.featureName);

function analytics(action, featureName) {
  window.rzpAnalytics?.({
    eventCategory: 'Dashboard - Settings',
    eventAction: `${action} - ${featureName}`,
  });
}

export function trackFlashCheckoutInitiate(isFlashCheckoutEnabled: boolean) {
  selfServeTrackInitiate({
    selfServeAction: `${flashCheckoutProps.featureName} ${
      isFlashCheckoutEnabled ? 'Enabled' : 'Disabled'
    }`,
    page: 'Config',
    screen: 'Settings',
  });
  analyticsTrack({
    objectName: analyticsObjName,
    actionName: 'toggled',
    screen: 'settings',
    properties: {
      location: 'configuration',
      [analyticsLabel]: isFlashCheckoutEnabled ? 'Enabled' : 'Disabled',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
}

export function trackFlashCheckoutSuccess(isFlashCheckoutEnabled: boolean) {
  if (isFlashCheckoutEnabled) {
    analytics('Enable', flashCheckoutProps.featureName);
  } else {
    analytics('Disable', flashCheckoutProps.featureName);
  }
  selfServeTrackSuccess({
    selfServeAction: `${flashCheckoutProps.featureName} ${
      isFlashCheckoutEnabled ? 'Enabled' : 'Disabled'
    }`,
    page: 'Config',
    screen: 'Settings',
  });
  analyticsTrack({
    objectName: `${analyticsObjName} toggle`,
    actionName: 'result',
    screen: 'settings',
    properties: {
      location: 'configuration',
      [analyticsLabel]: isFlashCheckoutEnabled ? 'Enabled' : 'Disabled',
      ...{ status: 'Success' },
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
}

export function trackFlashCheckoutFailure(isFlashCheckoutEnabled: boolean, failureReason: string) {
  analyticsTrack({
    objectName: `${analyticsObjName} toggle`,
    actionName: 'result',
    screen: 'settings',
    properties: {
      location: 'configuration',
      [analyticsLabel]: isFlashCheckoutEnabled ? 'Enabled' : 'Disabled',
      ...{
        status: 'Failure',
        failureReason,
      },
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
}
