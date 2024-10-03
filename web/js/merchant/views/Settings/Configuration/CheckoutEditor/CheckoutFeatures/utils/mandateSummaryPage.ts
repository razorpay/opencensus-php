import { analyticsTrack } from 'common/utils/analytics';
import { camelize, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { skipCardMandateSummaryProps } from 'merchant/views/Settings/Configuration/settings-config-constants';

const analyticsObjName = skipCardMandateSummaryProps.featureName.toLowerCase();
const analyticsLabel = camelize(skipCardMandateSummaryProps.featureName);

function analytics(action, featureName) {
  window.rzpAnalytics?.({
    eventCategory: 'Dashboard - Settings',
    eventAction: `${action} - ${featureName}`,
  });
}

export function trackMandateSummaryPageInitiate(isMandatorySummaryPageEnabled: boolean) {
  selfServeTrackInitiate({
    selfServeAction: `${skipCardMandateSummaryProps.featureName} ${
      isMandatorySummaryPageEnabled ? 'Enabled' : 'Disabled'
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
      [analyticsLabel]: isMandatorySummaryPageEnabled ? 'Enabled' : 'Disabled',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
}

export function trackMandateSummaryPageSuccess(isMandatorySummaryPageEnabled: boolean) {
  if (isMandatorySummaryPageEnabled) {
    analytics('Enable', skipCardMandateSummaryProps.featureName);
  } else {
    analytics('Disable', skipCardMandateSummaryProps.featureName);
  }
  selfServeTrackSuccess({
    selfServeAction: `${skipCardMandateSummaryProps.featureName} ${
      isMandatorySummaryPageEnabled ? 'Enabled' : 'Disabled'
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
      [analyticsLabel]: isMandatorySummaryPageEnabled ? 'Enabled' : 'Disabled',
      ...{ status: 'Success' },
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
}

export function trackMandateSummaryPageFailure(
  isMandatorySummaryPageEnabled: boolean,
  failureReason: string,
) {
  analyticsTrack({
    objectName: `${analyticsObjName} toggle`,
    actionName: 'result',
    screen: 'settings',
    properties: {
      location: 'configuration',
      [analyticsLabel]: isMandatorySummaryPageEnabled ? 'Enabled' : 'Disabled',
      ...{
        status: 'Failure',
        failureReason,
      },
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
}
