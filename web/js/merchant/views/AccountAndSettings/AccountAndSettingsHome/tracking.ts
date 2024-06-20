import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export interface Track {
  objectName: string;
  actionName?: string;
  screen?: string;
  properties?: Record<string, unknown>;
}

const SCREEN = 'Account and Settings';

export const track = ({
  objectName,
  actionName = 'Clicked',
  screen = SCREEN,
  properties,
}: Track): void => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      version: 'v2',
      page: SCREEN,
      ...properties,
      session_id: window.session_id,
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
    },
  });
};

export const trackAccountAndSettingsLoad = () => {
  track({
    objectName: 'Account And Settings Page',
    actionName: 'Displayed',
  });
};
